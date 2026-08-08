<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\RestorePost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class RestorePostData extends Data
{
    public function __construct(
        public User           $actorUser,
        public PostIdentifier $id,
    )
    {
    }
}
