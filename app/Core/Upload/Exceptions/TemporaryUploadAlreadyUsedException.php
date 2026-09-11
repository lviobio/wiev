<?php
declare(strict_types=1);

namespace App\Core\Upload\Exceptions;

use RuntimeException;

/**
 * Thrown inside the flush() transaction when the staged file being turned into media
 * was claimed by another, concurrent request first - the transaction rolls back and
 * nothing of this request survives.
 */
class TemporaryUploadAlreadyUsedException extends RuntimeException
{
    public static function make(string $disk, string $path): self
    {
        return new self(sprintf('The uploaded file "%s" on disk "%s" has already been used.', $path, $disk));
    }
}
