<?php
declare(strict_types=1);

return [
    /*
     * Disk temporary uploads are staged on before being claimed by a real model.
     *
     * Any disk works - claiming goes through media library's addMediaFromDisk(),
     * which copies from wherever this points to the media disk (see
     * App\Core\ModelManager\InteractsWithMedia::addMedia()). It should not be a
     * publicly served one, though: an unclaimed file must be reachable only through
     * application code that already checks ownership, which "public" (symlinked into
     * the web root) would defeat.
     */
    'disk' => env('TEMPORARY_UPLOAD_DISK', 'local'),

    'directory' => env('TEMPORARY_UPLOAD_DIRECTORY', 'temporary-uploads'),

    /*
     * How long an unclaimed upload survives before App\Core\Upload\Console\Commands\
     * PruneTemporaryUploadsCommand deletes it.
     */
    'ttl_hours' => (int) env('TEMPORARY_UPLOAD_TTL_HOURS', 24 * 7),

    /*
     * How long a used upload stays on the staging disk - and stays usable by its owner
     * under the same identifier - after it was first turned into media. The media
     * write then copies the file instead of moving it, so any number of requests may
     * reference it until the window closes; the prune command deletes it afterwards.
     *
     * 0 means single use: the staged file is moved into media storage on commit and
     * the identifier can't be referenced again.
     */
    'keep_after_use_minutes' => (int) env('TEMPORARY_UPLOAD_KEEP_AFTER_USE_MINUTES', 0),

    /*
     * Single-use mode only. A used upload whose staged file is still on disk is
     * either mid-flight (marked at commit, the file move a moment away) or a request
     * that died right after committing. The prune command leaves such rows alone
     * this long before deleting them, so the second case can still be inspected.
     */
    'used_grace_minutes' => (int) env('TEMPORARY_UPLOAD_USED_GRACE_MINUTES', 60),

    /*
     * Chunked uploads (App\Core\Upload\Actions\Chunked) - large files staged in
     * pieces instead of one multipart request, ending in the same TemporaryUpload
     * a single-shot /uploads call would produce. See App\Core\Upload\Chunked\
     * ChunkedUploadStrategyResolver for how the assembly mechanics depend on
     * whether `disk` above is a local or an S3-driven disk.
     */
    'chunked' => [
        // Cap on the fully assembled file - independent of media-library.max_file_size
        // (which bounds a single ordinary multipart request; chunking exists to get
        // past that, not to inherit it).
        'max_size' => (int) env('TEMPORARY_UPLOAD_CHUNKED_MAX_SIZE', 1024 * 1024 * 1024), // 1GB

        // Caps one chunk request - independent of the frontend's own chunk-size
        // default, so a misbehaving/other client can't send an arbitrarily large
        // single chunk and defeat the point of chunking.
        'chunk_max_size' => (int) env('TEMPORARY_UPLOAD_CHUNKED_CHUNK_MAX_SIZE', 1024 * 1024 * 20), // 20MB

        // How long an abandoned session (no chunk received / never completed)
        // survives before App\Core\Upload\Console\Commands\PruneChunkedUploadsCommand
        // deletes it.
        'ttl_hours' => (int) env('TEMPORARY_UPLOAD_CHUNKED_TTL_HOURS', 24),
    ],
];
