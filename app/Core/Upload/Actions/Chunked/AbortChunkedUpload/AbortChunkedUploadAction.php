<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\AbortChunkedUpload;

use App\Core\Upload\Chunked\ChunkedUploadStrategyResolver;
use App\Core\Upload\Models\ChunkedUpload;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a session before it completes - the client removed the file, or
 * `uploads:prune-chunked` reaped an abandoned one.
 */
readonly class AbortChunkedUploadAction
{
    public function __construct(
        private ChunkedUploadStrategyResolver $strategies,
    )
    {
    }

    public function __invoke(AbortChunkedUploadData $data): void
    {
        DB::transaction(function () use ($data): void {
            $session = ChunkedUpload::query()
                ->ownedBy($data->actorUser->getKey())
                ->where('uuid', $data->id->value)
                ->lockForUpdate()
                ->firstOrFail();

            $this->strategies->resolve($session->disk)->abort($session);

            $session->delete();
        });
    }
}
