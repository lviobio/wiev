<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\AttachFile;

use App\Models\User;
use App\Modules\Post\VO\PostFile;
use App\Modules\Post\VO\PostIdentifier;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(required: ['file'])]
class AttachFileData extends Data
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'binary')]
        public PostFile       $file,

        public User           $actorUser,
        public PostIdentifier $id,
    )
    {
    }
}
