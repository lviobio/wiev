<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\CompleteChunkedUpload;

use App\Core\Upload\Chunked\ChunkedUploadStrategyResolver;
use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Finishes a session: hands it to the strategy to produce a real
 * {@see TemporaryUpload} - claimable by `PostFile`/`PostCover` exactly like one
 * produced by the single-shot `/uploads` endpoint - then discards the session row.
 * The resulting `TemporaryUpload` is the durable record from here on, same as
 * `StoreUploadAction`'s.
 *
 * Lives on `App\Core\Upload\Http\Controllers\UploadController`
 * ({@see \App\Core\Upload\Http\generator}), not `ChunkedUploadController`, because the
 * generator ties a method's return type to its controller's one `->resource()`, and
 * this one returns a `TemporaryUpload` - `UploadController`'s resource - not a
 * `ChunkedUpload`.
 */
readonly class CompleteChunkedUploadAction
{
    public function __construct(
        private ChunkedUploadStrategyResolver $strategies,
    )
    {
    }

    public function __invoke(CompleteChunkedUploadData $data): TemporaryUpload
    {
        return DB::transaction(function () use ($data): TemporaryUpload {
            $session = ChunkedUpload::query()
                ->ownedBy($data->actorUser->getKey())
                ->where('uuid', $data->id->value)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$session->isComplete()) {
                throw ValidationException::withMessages([
                    'id' => [__('Not all chunks have been received yet.')],
                ]);
            }

            $upload = $this->strategies->resolve($session->disk)->complete($session);

            $session->delete();

            return $upload;
        });
    }
}
