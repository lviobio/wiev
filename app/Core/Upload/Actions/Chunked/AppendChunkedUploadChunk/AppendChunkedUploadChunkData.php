<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\AppendChunkedUploadChunk;

use App\Core\Upload\VO\ChunkedUploadIdentifier;
use App\Core\Upload\VO\UploadedChunk;
use App\Models\User;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AppendChunkedUploadChunkData extends Data
{
    public function __construct(
        public UploadedChunk $chunk,

        #[Required]
        #[IntegerType]
        #[Min(0)]
        public int $offset,

        public User $actorUser,
        public ChunkedUploadIdentifier $id,
    )
    {
    }
}
