<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\RenameFile;

use App\Models\User;
use App\Modules\Post\Domain\VO\PostFileName;
use App\Modules\Post\VO\PostFileIdentifier;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class RenameFileData extends Data
{
    public function __construct(
        public PostFileName       $name,

        public User               $actorUser,
        public PostIdentifier     $id,
        public PostFileIdentifier $fileId,
    )
    {
    }
}
