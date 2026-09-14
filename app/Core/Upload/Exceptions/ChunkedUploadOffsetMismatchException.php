<?php
declare(strict_types=1);

namespace App\Core\Upload\Exceptions;

use RuntimeException;

/**
 * A chunk's declared `offset` didn't match the session's `received_bytes` - either a
 * retry racing a request that already landed, or the client lost track of how much it
 * has actually sent. Carries the real `received_bytes` so the client can resync and
 * retry from there rather than restarting the whole upload.
 */
class ChunkedUploadOffsetMismatchException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $receivedBytes,
    ) {
        parent::__construct($message);
    }

    public static function make(int $expected, int $given): self
    {
        return new self(
            sprintf('Expected chunk offset %d, got %d.', $expected, $given),
            receivedBytes: $expected,
        );
    }
}
