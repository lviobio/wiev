<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\StartChunkedUpload;

use App\Models\User;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Declares a file the client is about to stage in pieces - no bytes yet, just what to
 * expect. `totalSize`'s only shape check here is "a positive integer"; the real cap
 * (`uploads.chunked.max_size`) is enforced in the Action, not as a rule attribute,
 * because it's a runtime config value and attribute arguments must be compile-time
 * constants (the same reason `FileRule`/`TemporaryUploadRule` build their `max:...`
 * rule in a method body rather than an attribute).
 */
class StartChunkedUploadData extends Data
{
    public function __construct(
        #[Required]
        #[StringType]
        #[Max(255)]
        public string $originalName,

        #[Nullable]
        #[StringType]
        public ?string $mimeType,

        #[Required]
        #[IntegerType]
        #[Min(1)]
        public int $totalSize,

        public User $actorUser,
    )
    {
    }
}
