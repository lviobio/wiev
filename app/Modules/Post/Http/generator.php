<?php
declare(strict_types=1);

use App\Enums\AuthAbilityEnum;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\DestroyPost\DestroyPostAction;
use App\Modules\Post\Actions\RestorePost\RestorePostAction;
use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Modules\Post\Actions\UpdatePost\UpdatePostAction;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Http\Controllers\PostController;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Modules\Post\VO\PostIdentifier;
use App\Support\Data\Filling\FillFromAuthenticatedUser;
use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\HttpMethod;
use App\Support\Http\Generator\Php\Gh;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

return ControllerDefinition::make(PostController::class)
    ->model(Post::class)
    ->resource(PostResource::class)
    ->endpoints(
        Endpoint::index(PostIndexQuery::class)
            ->ability(AuthAbilityEnum::Access, Post::class),

        Endpoint::show(ShowPostAction::class)
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::store(CreatePostAction::class)
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->fill(
                new FillFromAuthenticatedUser('authorUser'),
            ),

        Endpoint::update(UpdatePostAction::class)
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::destroy(DestroyPostAction::class)
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        Endpoint::make(
            HttpMethod::Post,
            Endpoint::MODEL_PARAMETER . '/restore',
            controllerMethod: 'restore',
            actionClass: RestorePostAction::class,
        )
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->fill(
                new FillFromAuthenticatedUser('actorUser'),
                new FillFromRouteParameter('id', 'post'),
            ),

        // Small enough not to warrant an Action of its own.
        Endpoint::make(
            HttpMethod::Delete,
            Endpoint::MODEL_PARAMETER . '/cover',
            controllerMethod: 'removeCover',
            routeName: 'cover.destroy',
            callback: function (Request $request): Response {
                $id = PostIdentifier::fromRequestParameter($request, 'post');
                $model = Post::query()->findOrFail($id);

                Gate::forUser($request->user())->authorize('update', $model);

                $model->clearMediaCollection(PostMediaCollectionEnum::Cover->value);

                return response()->noContent();
            },
        )
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->responses(
                Gh::response(204, 'Post cover removed'),
            ),
    );
