<?php
declare(strict_types=1);

namespace App\Core\Upload\Console\Commands;

use App\Core\Upload\Chunked\ChunkedUploadStrategyResolver;
use App\Core\Upload\Models\ChunkedUpload;
use Illuminate\Console\Command;

/**
 * Deletes chunked-upload sessions abandoned before completion - a browser tab closed
 * mid-upload, a network drop the client never resumed. Separate from
 * `uploads:prune` ({@see PruneTemporaryUploadsCommand}): a different table, a
 * different lifecycle (a session is never "used and kept", it either completes and
 * disappears or it doesn't).
 */
final class PruneChunkedUploadsCommand extends Command
{
    protected $signature = 'uploads:prune-chunked';

    protected $description = 'Delete abandoned chunked-upload sessions';

    public function handle(ChunkedUploadStrategyResolver $strategies): int
    {
        $cutoff = now()->subHours((int) config('uploads.chunked.ttl_hours'));

        $stale = ChunkedUpload::query()
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $session) {
            // Resolving per-session, not once up front: each session was allocated
            // on the disk that was configured when it started, which may not be
            // `uploads.disk`'s current value.
            $strategies->resolve($session->disk)->abort($session);
            $session->delete();
        }

        $this->components->info(sprintf('Pruned %d abandoned chunked upload(s).', $stale->count()));

        return self::SUCCESS;
    }
}
