<?php
declare(strict_types=1);

namespace App\Core\Upload\Chunked;

use App\Core\Upload\Exceptions\UnsupportedChunkedUploadDiskException;
use Illuminate\Contracts\Container\Container;

/**
 * Picks the {@see ChunkedUploadStrategy} that matches a disk's actual driver, so
 * chunked uploads work correctly whichever disk `uploads.disk` names - today `local`
 * or `s3` (see `config/filesystems.php`), resolved from the disk's own configuration
 * rather than assumed from `uploads.disk`'s current value, so a session started under
 * one config keeps working if the disk it was allocated on doesn't change identity.
 */
final readonly class ChunkedUploadStrategyResolver
{
    public function __construct(
        private Container $container,
    )
    {
    }

    public function resolve(string $disk): ChunkedUploadStrategy
    {
        $driver = config("filesystems.disks.{$disk}.driver");

        return match ($driver) {
            'local' => $this->container->make(LocalChunkedUploadStrategy::class),
            's3' => $this->container->make(S3ChunkedUploadStrategy::class),
            default => throw UnsupportedChunkedUploadDiskException::make($disk, $driver),
        };
    }
}
