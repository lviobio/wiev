<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\DestroyPost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Attributes\FromAuthenticatedUser;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

class DestroyPostData extends Data
{
    #[FromAuthenticatedUser]
    public User $actorUser;

    #[FromRouteParameter('post')]
    public PostIdentifier $id;
}
