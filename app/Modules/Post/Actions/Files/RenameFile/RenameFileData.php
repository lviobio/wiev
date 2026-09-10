<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\RenameFile;

use App\Models\User;
use App\Modules\Post\Domain\VO\PostFileName;
use App\Modules\Post\VO\PostFileIdentifier;
use App\Modules\Post\VO\PostIdentifier;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(required: ['name'])]
class RenameFileData extends Data
{
    public function __construct(
        #[OA\Property(type: 'string')]
        public PostFileName       $name,

        public User               $actorUser,
        public PostIdentifier     $id,
        public PostFileIdentifier $fileId,
    )
    {
    }
}
