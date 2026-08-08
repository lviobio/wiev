<?php
declare(strict_types=1);

use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\DestroyPost\DestroyPostAction;
use App\Modules\Post\Actions\RemoveCover\RemoveCoverAction;
use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Modules\Post\Actions\UpdatePost\UpdatePostAction;
use App\Modules\Post\Http\Controllers\PostController;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Support\Data\Filling\FillFromAuthenticatedUser;
use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\HttpMethod;

return ControllerDefinition::make(PostController::class)
    ->model(Post::class)
    ->resource(PostResource::class)
    ->endpoints(
        Endpoint::index(PostIndexQuery::class),

        Endpoint::show(ShowPostAction::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::store(CreatePostAction::class)
            ->fill(
                new FillFromAuthenticatedUser('authorUser'),
            ),

        Endpoint::update(UpdatePostAction::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::destroy(DestroyPostAction::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::make(
            HttpMethod::Delete,
            Endpoint::MODEL_PARAMETER . '/cover',
            controllerMethod: 'removeCover',
            routeName: 'cover.destroy',
            actionClass: RemoveCoverAction::class,
        )
            ->summary('Remove post cover')
            ->operationId('removePostCover')
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),
    );
