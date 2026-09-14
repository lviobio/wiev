<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Chunked\ChunkedUploadStrategyResolver;
use App\Core\Upload\Chunked\LocalChunkedUploadStrategy;
use App\Core\Upload\Chunked\S3ChunkedUploadStrategy;
use App\Core\Upload\Exceptions\UnsupportedChunkedUploadDiskException;

test('a local disk resolves to the local strategy', function () {
    config(['filesystems.disks.local-test' => ['driver' => 'local', 'root' => storage_path('app/local-test')]]);

    $strategy = resolve(ChunkedUploadStrategyResolver::class)->resolve('local-test');

    expect($strategy)->toBeInstanceOf(LocalChunkedUploadStrategy::class);
});

test('an s3 disk resolves to the s3 strategy', function () {
    $strategy = resolve(ChunkedUploadStrategyResolver::class)->resolve('s3');

    expect($strategy)->toBeInstanceOf(S3ChunkedUploadStrategy::class);
});

test('an unsupported disk driver fails loudly, naming the disk and driver', function () {
    config(['filesystems.disks.weird' => ['driver' => 'ftp']]);

    expect(fn() => resolve(ChunkedUploadStrategyResolver::class)->resolve('weird'))
        ->toThrow(UnsupportedChunkedUploadDiskException::class, 'No chunked-upload strategy for disk "weird" (driver "ftp").');
});

test('an unknown disk fails loudly rather than resolving something unexpected', function () {
    expect(fn() => resolve(ChunkedUploadStrategyResolver::class)->resolve('does-not-exist'))
        ->toThrow(UnsupportedChunkedUploadDiskException::class);
});
