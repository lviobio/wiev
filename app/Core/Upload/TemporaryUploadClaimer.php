<?php
declare(strict_types=1);

namespace App\Core\Upload;

use App\Core\Upload\Exceptions\TemporaryUploadAlreadyUsedException;
use App\Core\Upload\Models\TemporaryUpload;

/**
 * Marks a staged file as used, inside the flush() transaction, right before commit.
 *
 * Called from {@see \App\Support\Spatie\MediaLibrary\DeferredFileAdder}. That timing
 * is the whole design:
 *
 *  - Nothing is marked until the request actually reaches commit. A validation
 *    failure, a domain-level check, an exception - or a `dd()` - anywhere before
 *    that leaves the upload untouched, with nothing to undo.
 *  - The mark rolls back with the transaction, for free.
 *
 * What "used" then means depends on `uploads.keep_after_use_minutes`:
 *
 *  - Single use (0): the media write moves the file, so two requests must never both
 *    get it. The database is the lock: a concurrent request running the same
 *    conditional UPDATE blocks on the row until the first one commits, then sees
 *    0 rows affected and rolls back with {@see TemporaryUploadAlreadyUsedException}.
 *  - Kept for a while (> 0): the media write copies the file and the staged original
 *    stays, so there is nothing to race over - the first use just starts the
 *    retention clock, and the owner may reference the same identifier again until it
 *    runs out ({@see TemporaryUpload::scopeUsableBy()}).
 *
 * A file on a disk that no temporary upload knows about isn't ours and is left
 * alone - `addMediaFromDisk()` has legitimate uses beyond staged uploads.
 */
final readonly class TemporaryUploadClaimer
{
    /**
     * @return bool whether the staged original must be kept after the media write
     */
    public function claim(string $disk, string $path): bool
    {
        // (disk, path) is the identity media library gives us for the file it's
        // writing, and it is unique per upload: every staged file lives in its own
        // uuid directory (StoreUploadAction), and the table enforces the uniqueness.
        $upload = TemporaryUpload::query()
            ->where('disk', $disk)
            ->where('path', $path)
            ->first();

        if ($upload === null) {
            return false;
        }

        if (TemporaryUpload::keptAfterUseFor() !== null) {
            TemporaryUpload::query()
                ->whereKey($upload->getKey())
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return true;
        }

        $claimed = TemporaryUpload::query()
            ->whereKey($upload->getKey())
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($claimed === 0) {
            throw TemporaryUploadAlreadyUsedException::make($disk, $path);
        }

        return false;
    }
}
