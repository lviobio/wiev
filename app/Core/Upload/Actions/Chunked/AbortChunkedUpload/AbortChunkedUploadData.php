<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\AbortChunkedUpload;

use App\Core\Upload\VO\ChunkedUploadIdentifier;
use App\Models\User;
use Spatie\LaravelData\Data;

class AbortChunkedUploadData extends Data
{
    public function __construct(
        public User $actorUser,
        public ChunkedUploadIdentifier $id,
    )
    {
    }
}
