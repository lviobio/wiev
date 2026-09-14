<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\StartChunkedUpload;

use App\Core\Upload\Chunked\ChunkedUploadStrategyResolver;
use App\Core\Upload\Models\ChunkedUpload;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Opens a chunked-upload session: allocates its final disk/path (same per-upload uuid
 * directory convention as {@see \App\Core\Upload\Actions\StoreUpload\StoreUploadAction})
 * and lets the resolved strategy do whatever it needs before the first chunk arrives.
 *
 * Standalone, like `StoreUploadAction` - not run through `ModelManager`, not part of
 * any aggregate's transactional graph.
 */
readonly class StartChunkedUploadAction
{
    public function __construct(
        private ChunkedUploadStrategyResolver $strategies,
    )
    {
    }

    public function __invoke(StartChunkedUploadData $data): ChunkedUpload
    {
        $maxSize = (int) config('uploads.chunked.max_size');

        if ($data->totalSize > $maxSize) {
            throw ValidationException::withMessages([
                'totalSize' => [__('The file must not be larger than :max bytes.', ['max' => $maxSize])],
            ]);
        }

        $disk = config('uploads.disk');
        $uuid = (string) Str::uuid();
        $path = config('uploads.directory') . '/' . $uuid . '/' . $data->originalName;

        $session = new ChunkedUpload([
            'uuid' => $uuid,
            'user_id' => $data->actorUser->getKey(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $data->originalName,
            'mime_type' => $data->mimeType,
            'total_size' => $data->totalSize,
            'received_bytes' => 0,
        ]);

        try {
            $this->strategies->resolve($disk)->start($session);
            $session->save();
        } catch (Throwable $exception) {
            // start() may have already reserved something (an S3 multipart upload, a
            // local directory) before failing on the save() itself - abort rather than
            // leaving it orphaned. Best-effort: if this itself fails, the original
            // exception is still what surfaces.
            try {
                $this->strategies->resolve($disk)->abort($session);
            } catch (Throwable) {
                // ignore - see above
            }

            throw $exception;
        }

        return $session;
    }
}
