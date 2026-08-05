<?php
declare(strict_types=1);

use App\Modules\Post\Http\Controllers\PostController;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;

/**
 * A declaration file exposing two controllers for one model - the case the locator has
 * to accept, and the reason route markers are keyed by controller rather than module.
 *
 * Lives under tests/Fixtures rather than app/ so the locator's own scan ignores it.
 */
return [
    ControllerDefinition::make(PostController::class)
        ->model(Post::class)
        ->resource(PostResource::class)
        ->endpoints(
            Endpoint::index(PostIndexQuery::class),
        ),

    ControllerDefinition::make(App\Modules\Post\Http\Controllers\PostArchiveController::class)
        ->model(Post::class)
        ->resource(PostResource::class)
        ->routePrefix('archived-posts')
        ->tag('archive')
        ->endpoints(
            Endpoint::index(PostIndexQuery::class),
        ),
];
