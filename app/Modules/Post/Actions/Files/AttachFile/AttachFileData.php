<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\AttachFile;

use App\Models\User;
use App\Modules\Post\Domain\VO\PostFile;
use App\Modules\Post\Domain\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class AttachFileData extends Data
{
    public function __construct(
        public PostFile       $file,

        public User           $actorUser,
        public PostIdentifier $id,
    )
    {
    }
}
