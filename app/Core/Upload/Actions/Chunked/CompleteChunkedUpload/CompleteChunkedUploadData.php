<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\CompleteChunkedUpload;

use App\Core\Upload\VO\ChunkedUploadIdentifier;
use App\Models\User;
use Spatie\LaravelData\Data;

class CompleteChunkedUploadData extends Data
{
    public function __construct(
        public User $actorUser,
        public ChunkedUploadIdentifier $id,
    )
    {
    }
}
