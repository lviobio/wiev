<?php
declare(strict_types=1);

namespace App\Core\Upload\Exceptions;

use LogicException;

/**
 * `uploads.disk` resolved to a filesystem driver
 * {@see \App\Core\Upload\Chunked\ChunkedUploadStrategyResolver} has no assembly
 * strategy for. A configuration problem, not a client one - a new disk driver needs a
 * new `ChunkedUploadStrategy` before chunked uploads can use it, rather than silently
 * misbehaving (partial writes, a strategy assuming semantics the driver doesn't have).
 */
class UnsupportedChunkedUploadDiskException extends LogicException
{
    public static function make(string $disk, ?string $driver): self
    {
        return new self(sprintf(
            'No chunked-upload strategy for disk "%s" (driver "%s").',
            $disk,
            $driver ?? 'unknown',
        ));
    }
}
