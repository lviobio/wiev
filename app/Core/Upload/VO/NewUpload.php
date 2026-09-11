<?php
declare(strict_types=1);

namespace App\Core\Upload\VO;

use App\Core\VO\UploadedFileValue;
use App\Support\Validation\FileRule;

/**
 * The raw file a client sends when staging a temporary upload - the one place in the
 * app where a request carries file bytes rather than a reference to a staged file.
 */
readonly class NewUpload extends UploadedFileValue
{
    public static function rules(): array
    {
        return FileRule::make()->toArray();
    }
}
