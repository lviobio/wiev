<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\AppendChunkedUploadChunk;

use App\Core\Upload\Chunked\ChunkedUploadStrategyResolver;
use App\Core\Upload\Exceptions\ChunkedUploadOffsetMismatchException;
use App\Core\Upload\Models\ChunkedUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Appends one chunk to a session. The offset check and the write happen inside one
 * transaction, with the session row locked for the duration
 * (`lockForUpdate()`) - the same shape of fix as
 * {@see \App\Core\Upload\TemporaryUploadClaimer}'s race handling, applied to a
 * different race: two requests for the same session (a genuine retry racing the
 * original, or two tabs) must not both believe they own the next offset.
 */
readonly class AppendChunkedUploadChunkAction
{
    public function __construct(
        private ChunkedUploadStrategyResolver $strategies,
    )
    {
    }

    public function __invoke(AppendChunkedUploadChunkData $data): ChunkedUpload
    {
        return DB::transaction(function () use ($data): ChunkedUpload {
            $session = ChunkedUpload::query()
                ->ownedBy($data->actorUser->getKey())
                ->where('uuid', $data->id->value)
                ->lockForUpdate()
                ->firstOrFail();

            if ($data->offset !== $session->received_bytes) {
                // Not a lost race in the claim sense - nothing has been consumed, the
                // client just doesn't have the up-to-date offset. Carries the real one
                // so it can resync instead of restarting the whole upload.
                throw ChunkedUploadOffsetMismatchException::make($session->received_bytes, $data->offset);
            }

            if ($session->received_bytes + $data->chunk->size > $session->total_size) {
                throw ValidationException::withMessages([
                    'chunk' => [__('This chunk would exceed the declared file size.')],
                ]);
            }

            $this->strategies->resolve($session->disk)->appendChunk($session, $data->chunk->source, $data->offset);

            $session->received_bytes += $data->chunk->size;
            $session->save();

            return $session;
        });
    }
}
