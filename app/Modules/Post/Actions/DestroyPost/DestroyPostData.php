<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\DestroyPost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class DestroyPostData extends Data
{
    public User $actorUser;

    public PostIdentifier $id;
}
