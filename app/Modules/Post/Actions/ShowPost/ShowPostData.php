<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\ShowPost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class ShowPostData extends Data
{
    public User $actorUser;

    public PostIdentifier $id;
}