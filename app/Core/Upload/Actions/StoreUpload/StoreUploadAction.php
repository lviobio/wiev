<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\StoreUpload;

use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Stages a client-uploaded file so it can be referenced by id, not re-sent as bytes,
 * by whatever request later claims it (e.g. {@see \App\Modules\Post\Domain\VO\PostCover}).
 *
 * Not run through ModelManager: a standalone row, not part of any aggregate's
 * transactional graph.
 */
readonly class StoreUploadAction
{
    public function __invoke(StoreUploadData $data): TemporaryUpload
    {
        $disk = config('uploads.disk');
        $uuid = (string) Str::uuid();
        $directory = config('uploads.directory') . '/' . $uuid;

        $storedPath = Storage::disk($disk)->putFileAs(
            $directory,
            $data->file->source,
            $data->file->originalName,
        );

        if ($storedPath === false) {
            throw new RuntimeException('Failed to store the uploaded file.');
        }

        try {
            return TemporaryUpload::query()->create([
                'uuid' => $uuid,
                'user_id' => $data->actorUser->getKey(),
                'disk' => $disk,
                'path' => $storedPath,
                'original_name' => $data->file->originalName,
                'mime_type' => $data->file->mimeType,
                'size' => $data->file->size,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);

            throw $exception;
        }
    }
}
