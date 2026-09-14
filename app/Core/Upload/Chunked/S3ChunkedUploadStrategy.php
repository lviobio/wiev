<?php
declare(strict_types=1);

namespace App\Core\Upload\Chunked;

use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

/**
 * Assembly for an S3-backed disk, via S3's real multipart-upload API
 * (createMultipartUpload / uploadPart / completeMultipartUpload /
 * abortMultipartUpload) - there is no "write at an offset" operation to fall back on
 * the way {@see LocalChunkedUploadStrategy} does.
 *
 * S3 requires every part but the last to be at least 5MB, which rarely lines up with
 * the frontend's own chunk size, so incoming chunks are buffered - on Laravel's own
 * `local` disk, deliberately *not* `uploads.disk` (this strategy exists because that
 * one isn't local) - until the buffer clears the minimum, then flushed as one part and
 * cleared. Local disk usage for a session therefore never exceeds ~5MB regardless of
 * the total file size. The buffer is a real file, not an in-memory/cached string:
 * nothing else in `App\Core\Upload` keeps state that matters in `Cache`, on the same
 * reasoning that motivated {@see \App\Core\Upload\TemporaryUploadClaimer}'s
 * claim-at-commit design - a cache eviction or a restart shouldn't be able to silently
 * lose an in-flight upload's progress.
 *
 * `upload_id` and the accumulated `parts` (S3's ETags, keyed by part number) live in
 * `$session->provider_state` - this strategy mutates it in memory only, exactly like
 * the buffer file's byte count is implied by the filesystem rather than tracked
 * separately; {@see \App\Core\Upload\Actions\Chunked} persists the session row.
 */
final readonly class S3ChunkedUploadStrategy implements ChunkedUploadStrategy
{
    /** S3 rejects any non-final part smaller than this. */
    private const int MIN_PART_SIZE = 5 * 1024 * 1024;

    private const string BUFFER_DIRECTORY = 'chunked-upload-buffers';

    public function start(ChunkedUpload $session): void
    {
        Storage::disk('local')->makeDirectory(self::BUFFER_DIRECTORY);

        $adapter = $this->adapter($session);
        $config = $adapter->getConfig();

        $params = [
            'Bucket' => $config['bucket'],
            'Key' => $this->key($session),
            'ContentType' => $session->mime_type ?? 'application/octet-stream',
        ];

        if (($config['visibility'] ?? null) === 'public') {
            $params['ACL'] = 'public-read';
        }

        $result = $adapter->getClient()->createMultipartUpload($params);

        $session->provider_state = [
            'upload_id' => $result['UploadId'],
            'parts' => [],
        ];
    }

    public function appendChunk(ChunkedUpload $session, UploadedFile $chunk, int $offset): void
    {
        $handle = fopen($this->bufferPath($session), 'ab');

        if ($handle === false) {
            throw new RuntimeException("Could not open the chunk buffer for upload {$session->uuid}.");
        }

        try {
            fwrite($handle, $chunk->get());
        } finally {
            fclose($handle);
        }

        $this->flushBuffer($session, final: false);
    }

    public function complete(ChunkedUpload $session): TemporaryUpload
    {
        $this->flushBuffer($session, final: true);

        $adapter = $this->adapter($session);
        $state = $this->state($session);

        $parts = $state['parts'];
        usort($parts, static fn(array $a, array $b): int => $a['PartNumber'] <=> $b['PartNumber']);

        $adapter->getClient()->completeMultipartUpload([
            'Bucket' => $adapter->getConfig()['bucket'],
            'Key' => $this->key($session),
            'UploadId' => $state['upload_id'],
            'MultipartUpload' => ['Parts' => $parts],
        ]);

        $this->clearBuffer($session);

        return TemporaryUpload::query()->create([
            'uuid' => $session->uuid,
            'user_id' => $session->user_id,
            'disk' => $session->disk,
            'path' => $session->path,
            'original_name' => $session->original_name,
            // Client-declared, not sniffed: re-reading a possibly-huge object we just
            // finished writing, purely to run it through fileinfo, isn't worth it -
            // unlike the local strategy, which gets this for free from the real file.
            'mime_type' => $session->mime_type,
            'size' => $session->total_size,
        ]);
    }

    public function abort(ChunkedUpload $session): void
    {
        $state = $session->provider_state;
        $uploadId = $state['upload_id'] ?? null;

        if ($uploadId !== null) {
            $adapter = $this->adapter($session);

            // So S3 stops billing for parts nobody will ever complete.
            $adapter->getClient()->abortMultipartUpload([
                'Bucket' => $adapter->getConfig()['bucket'],
                'Key' => $this->key($session),
                'UploadId' => $uploadId,
            ]);
        }

        $this->clearBuffer($session);
    }

    /**
     * Uploads the buffer as one part once it clears S3's minimum - or unconditionally,
     * at any size (S3 allows a final part under 5MB), when this is the last chunk.
     */
    private function flushBuffer(ChunkedUpload $session, bool $final): void
    {
        $path = $this->bufferPath($session);
        $size = is_file($path) ? filesize($path) : 0;

        if ($size === 0 || (!$final && $size < self::MIN_PART_SIZE)) {
            return;
        }

        $body = file_get_contents($path);

        if ($body === false) {
            throw new RuntimeException("Could not read the chunk buffer for upload {$session->uuid}.");
        }

        $adapter = $this->adapter($session);
        $state = $this->state($session);
        $partNumber = count($state['parts']) + 1;

        $result = retry(
            times: 5,
            callback: fn() => $adapter->getClient()->uploadPart([
                'Bucket' => $adapter->getConfig()['bucket'],
                'Key' => $this->key($session),
                'PartNumber' => $partNumber,
                'UploadId' => $state['upload_id'],
                'Body' => $body,
            ]),
            sleepMilliseconds: 1000,
        );

        $state['parts'][] = ['ETag' => $result['ETag'], 'PartNumber' => $partNumber];
        $session->provider_state = $state;

        file_put_contents($path, '');
    }

    private function clearBuffer(ChunkedUpload $session): void
    {
        $path = $this->bufferPath($session);

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function bufferPath(ChunkedUpload $session): string
    {
        return Storage::disk('local')->path(self::BUFFER_DIRECTORY . '/' . $session->uuid . '.part');
    }

    /**
     * @return array{upload_id: string, parts: list<array{ETag: string, PartNumber: int}>}
     */
    private function state(ChunkedUpload $session): array
    {
        $state = $session->provider_state;

        if (!is_array($state) || !isset($state['upload_id'])) {
            throw new RuntimeException("Chunked upload {$session->uuid} has no multipart upload in progress.");
        }

        $state['parts'] ??= [];

        return $state;
    }

    /**
     * The S3 disk's own configured root, if any, applied by hand - everything here
     * calls the raw S3 client rather than going through Flysystem, which would
     * otherwise apply it automatically.
     */
    private function key(ChunkedUpload $session): string
    {
        $root = (string) ($this->adapter($session)->getConfig()['root'] ?? '');

        return trim($root . '/' . $session->path, '/');
    }

    private function adapter(ChunkedUpload $session): AwsS3V3Adapter
    {
        $disk = Storage::disk($session->disk);

        if (!$disk instanceof AwsS3V3Adapter) {
            throw new LogicException(sprintf(
                'Disk "%s" is not an S3 disk (%s).',
                $session->disk,
                $disk::class,
            ));
        }

        return $disk;
    }
}
