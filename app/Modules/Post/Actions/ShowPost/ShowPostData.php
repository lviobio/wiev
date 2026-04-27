<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\ShowPost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Attributes\FromAuthenticatedUser;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

class ShowPostData extends Data
{
    #[FromAuthenticatedUser]
    public User $actorUser;

    #[FromRouteParameter('post')]
    public PostIdentifier $id;
}