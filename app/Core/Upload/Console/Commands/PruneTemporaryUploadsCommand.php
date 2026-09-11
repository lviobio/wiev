<?php
declare(strict_types=1);

namespace App\Core\Upload\Console\Commands;

use App\Core\Upload\Models\TemporaryUpload;
use DateInterval;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes temporary uploads that are no longer useful.
 *
 *  - Used ones, once they are no longer kept. With `uploads.keep_after_use_minutes`
 *    set, that is the end of the reuse window (see TemporaryUpload::scopeUsableBy()).
 *    In single-use mode the staged file is normally already gone - media library
 *    deletes it once copied - and only the row is left; a used row whose file is
 *    still there is a request that died between commit and the file move, and gets
 *    `uploads.used_grace_minutes` before both go.
 *  - Never-used ones older than the TTL.
 */
final class PruneTemporaryUploadsCommand extends Command
{
    protected $signature = 'uploads:prune';

    protected $description = 'Delete used and expired temporary uploads';

    public function handle(): int
    {
        $kept = TemporaryUpload::keptAfterUseFor()
            ?? new DateInterval('PT' . (int) config('uploads.used_grace_minutes') . 'M');
        $used = TemporaryUpload::query()
            ->whereNotNull('used_at')
            ->get()
            ->filter(fn(TemporaryUpload $upload): bool => $upload->used_at < now()->sub($kept)
                || !Storage::disk($upload->disk)->exists($upload->path));
        $this->purge($used);

        $cutoff = now()->subHours((int) config('uploads.ttl_hours'));
        $expired = TemporaryUpload::query()
            ->whereNull('used_at')
            ->where('created_at', '<', $cutoff)
            ->get();
        $this->purge($expired);

        $this->components->info(sprintf(
            'Pruned %d used and %d expired upload(s).',
            $used->count(),
            $expired->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, TemporaryUpload>  $uploads
     */
    private function purge(Collection $uploads): void
    {
        foreach ($uploads as $upload) {
            // Each upload has a directory of its own (StoreUploadAction) - remove that,
            // not just the file: media library deletes only the file once it has
            // copied it, so the directory would otherwise outlive everything.
            Storage::disk($upload->disk)->deleteDirectory(dirname($upload->path));
            $upload->delete();
        }
    }
}
