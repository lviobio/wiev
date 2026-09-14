<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\DetachFile;

use App\Models\User;
use App\Modules\Post\Domain\VO\PostFileIdentifier;
use App\Modules\Post\Domain\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class DetachFileData extends Data
{
    public function __construct(
        public User               $actorUser,
        public PostIdentifier     $id,
        public PostFileIdentifier $fileId,
    )
    {
    }
}
