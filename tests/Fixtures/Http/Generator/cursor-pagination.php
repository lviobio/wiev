<?php
declare(strict_types=1);

use App\Modules\Post\Http\Controllers\PostFeedController;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;

/**
 * A listing paginated by cursor rather than by page - the shape an infinite feed needs.
 */
return ControllerDefinition::make(PostFeedController::class)
    ->model(Post::class)
    ->resource(PostResource::class)
    ->routePrefix('feed')
    ->endpoints(
        Endpoint::index(PostIndexQuery::class)->cursor(),
    );
