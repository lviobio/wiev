<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Chunked\S3ChunkedUploadStrategy;
use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Against the real MinIO service (compose.yaml) rather than Storage::fake() - a fake
 * disk has no S3 API behind it, so it can't exercise real multipart semantics (the
 * 5MB-per-part minimum, ETags, completeMultipartUpload actually assembling parts in
 * order). Skips itself if MinIO isn't reachable, so the suite still runs somewhere
 * without it (CI without the compose stack, say).
 */
beforeEach(function () {
    config(['uploads.disk' => 's3']);
    $this->disk = Storage::disk('s3');

    if (!$this->disk instanceof AwsS3V3Adapter) {
        $this->markTestSkipped('The "s3" disk is not S3-driven in this environment.');
    }

    try {
        $this->disk->exists('s3-connectivity-check');
    } catch (\Throwable $e) {
        $this->markTestSkipped('MinIO is not reachable: ' . $e->getMessage());
    }

    $this->strategy = resolve(S3ChunkedUploadStrategy::class);
});

afterEach(function () {
    // The buffer strategy uses Laravel's own `local` disk regardless of `uploads.disk`
    // - not faked here, so clean up anything a test left behind on the real disk.
    Storage::disk('local')->deleteDirectory('chunked-upload-buffers');
});

function s3ChunkedSession(array $overrides = []): ChunkedUpload
{
    return ChunkedUpload::factory()->create(['disk' => 's3', ...$overrides]);
}

test('a small round trip produces a real, byte-identical object', function () {
    $content = 'hello from a real multipart upload';
    $session = s3ChunkedSession(['total_size' => strlen($content), 'mime_type' => 'text/plain']);

    $this->strategy->start($session);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', $content), offset: 0);
    $upload = $this->strategy->complete($session);

    try {
        expect($upload)->toBeInstanceOf(TemporaryUpload::class)
            ->and($upload->disk)->toBe('s3')
            ->and($upload->path)->toBe($session->path)
            ->and($upload->size)->toBe(strlen($content))
            ->and($this->disk->exists($session->path))->toBeTrue()
            ->and($this->disk->get($session->path))->toBe($content);
    } finally {
        $this->disk->delete($session->path);
    }
});

test('a multi-part round trip (two parts clearing the 5MB minimum) assembles correctly', function () {
    $first = str_repeat('a', 5 * 1024 * 1024);
    $second = str_repeat('b', 1024);
    $session = s3ChunkedSession(['total_size' => strlen($first) + strlen($second)]);

    $this->strategy->start($session);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', $first), offset: 0);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('b.bin', $second), offset: strlen($first));
    $upload = $this->strategy->complete($session);

    try {
        expect($upload->size)->toBe(strlen($first) + strlen($second))
            ->and($this->disk->size($session->path))->toBe(strlen($first) + strlen($second));
    } finally {
        $this->disk->delete($session->path);
    }
});

test('abort releases the multipart upload and leaves no object behind', function () {
    $session = s3ChunkedSession(['total_size' => 5]);

    $this->strategy->start($session);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', 'hello'), offset: 0);

    $this->strategy->abort($session);

    $adapter = $this->disk;
    $config = $adapter->getConfig();
    $uploads = $adapter->getClient()->listMultipartUploads(['Bucket' => $config['bucket']])['Uploads'] ?? [];
    $keys = array_column($uploads, 'Key');

    expect($keys)->not->toContain($session->path)
        ->and($adapter->exists($session->path))->toBeFalse();
});
