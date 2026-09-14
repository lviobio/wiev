<?php
declare(strict_types=1);

namespace App\Core\Upload\Chunked;

use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Http\UploadedFile;

/**
 * How chunks actually get assembled into `$session->disk` / `$session->path` -
 * mechanics that genuinely differ by disk driver (a local disk supports random-offset
 * writes; S3 needs its own multipart-upload API instead), selected per session by
 * {@see ChunkedUploadStrategyResolver}. Everything above this interface (the Actions,
 * the HTTP layer) is identical regardless of which implementation is in play.
 */
interface ChunkedUploadStrategy
{
    /**
     * Called once, right after the session row is created.
     */
    public function start(ChunkedUpload $session): void;

    /**
     * Append one chunk's bytes to the assembly.
     *
     * `$offset` has already been checked to equal `$session->received_bytes` by the
     * caller ({@see \App\Core\Upload\Actions\Chunked\AppendChunkedUploadChunk\
     * AppendChunkedUploadChunkAction}), under a row lock - the strategy can trust it
     * and does not need to re-derive or re-check it.
     */
    public function appendChunk(ChunkedUpload $session, UploadedFile $chunk, int $offset): void;

    /**
     * All bytes are in (`$session->received_bytes === $session->total_size`, already
     * checked by the caller). Finish the assembly and produce the resulting upload.
     */
    public function complete(ChunkedUpload $session): TemporaryUpload;

    /**
     * The session is being abandoned (explicitly cancelled, or reaped by
     * `uploads:prune-chunked`) - release whatever the strategy is holding: a stray
     * directory, an open S3 multipart upload, a scratch buffer.
     */
    public function abort(ChunkedUpload $session): void;
}
