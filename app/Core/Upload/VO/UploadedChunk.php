<?php
declare(strict_types=1);

namespace App\Core\Upload\VO;

use App\Core\VO\UploadedFileValue;
use App\Support\Validation\FileRule;

/**
 * One piece of a file being staged via `App\Core\Upload\Actions\Chunked` - bytes
 * arriving in *this* request, same role {@see \App\Core\Upload\VO\NewUpload} plays for
 * a single-shot upload, just sized differently: capped at `uploads.chunked.
 * chunk_max_size`, not the whole-file `media-library.max_file_size` a chunk has no
 * reason to be bound by.
 */
readonly class UploadedChunk extends UploadedFileValue
{
    public static function rules(): array
    {
        return FileRule::make((int) config('uploads.chunked.chunk_max_size'))->toArray();
    }
}
