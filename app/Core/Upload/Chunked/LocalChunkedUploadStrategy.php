<?php
declare(strict_types=1);

namespace App\Core\Upload\Chunked;

use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Assembly for a local disk: a local filesystem supports writing at an arbitrary
 * byte offset, so there is nothing to "assemble" in the usual sense - each chunk
 * lands directly in its own range of the final file, which already lives at its
 * final `disk`/`path` from the very first byte. `complete()` is therefore just
 * bookkeeping: nothing is moved or copied.
 */
final readonly class LocalChunkedUploadStrategy implements ChunkedUploadStrategy
{
    public function start(ChunkedUpload $session): void
    {
        Storage::disk($session->disk)->makeDirectory(dirname($session->path));
    }

    public function appendChunk(ChunkedUpload $session, UploadedFile $chunk, int $offset): void
    {
        $path = Storage::disk($session->disk)->path($session->path);

        // 'c' creates the file if missing without truncating it if it already exists
        // (unlike 'w') and leaves the pointer at byte 0 (unlike 'a', which would
        // ignore fseek() and always write at EOF) - exactly what writing a chunk at an
        // arbitrary, already-verified offset needs.
        $handle = fopen($path, 'cb');

        if ($handle === false) {
            throw new RuntimeException("Could not open \"{$path}\" for writing.");
        }

        try {
            if (fseek($handle, $offset) !== 0) {
                throw new RuntimeException("Could not seek to offset {$offset} in \"{$path}\".");
            }

            $written = fwrite($handle, $chunk->get());

            if ($written !== $chunk->getSize()) {
                throw new RuntimeException("Short write to \"{$path}\": expected {$chunk->getSize()} bytes, wrote " . var_export($written, true) . '.');
            }
        } finally {
            fclose($handle);
        }
    }

    public function complete(ChunkedUpload $session): TemporaryUpload
    {
        $disk = Storage::disk($session->disk);

        $actualSize = $disk->size($session->path);

        if ($actualSize !== $session->total_size) {
            throw new RuntimeException(sprintf(
                'Assembled file "%s" is %d bytes, expected %d.',
                $session->path,
                $actualSize,
                $session->total_size,
            ));
        }

        return TemporaryUpload::query()->create([
            'uuid' => $session->uuid,
            'user_id' => $session->user_id,
            'disk' => $session->disk,
            'path' => $session->path,
            'original_name' => $session->original_name,
            // Sniffed from the real, now-complete file - the same provenance a
            // single-shot /uploads upload gets, unlike the S3 strategy (see its own
            // docblock for why that one can't afford to).
            'mime_type' => $disk->mimeType($session->path) ?: null,
            'size' => $actualSize,
        ]);
    }

    public function abort(ChunkedUpload $session): void
    {
        // Mirrors StoreUploadAction's per-upload uuid directory: remove the whole
        // thing, not just the partial file, so nothing is left behind.
        Storage::disk($session->disk)->deleteDirectory(dirname($session->path));
    }
}
