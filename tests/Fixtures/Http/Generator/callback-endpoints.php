<?php
declare(strict_types=1);

use App\Modules\Post\Http\Controllers\PostCallbackController;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\HttpMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Endpoints whose bodies are written inline rather than delegated to an Action.
 */
return ControllerDefinition::make(PostCallbackController::class)
    ->model(Post::class)
    ->resource(PostResource::class)
    ->endpoints(
        Endpoint::make(
            HttpMethod::Get,
            uri: 'echo',
            controllerMethod: 'echoTest',
            callback: function (Request $request) {
                return $request->input('echo');
            },
        )
            ->withoutAbility(),

        // Short bodies read fine as arrow functions.
        Endpoint::make(
            HttpMethod::Get,
            uri: 'shout',
            controllerMethod: 'shout',
            callback: fn(Request $request): string => Str::upper((string)$request->input('echo')),
        )
            ->withoutAbility(),

        // Classes the closure names are resolved against this file's `use` statements and
        // re-imported by the controller, even when it imports nothing of the sort itself.
        Endpoint::make(
            HttpMethod::Get,
            uri: 'latest',
            controllerMethod: 'latest',
            callback: function (Request $request): PostResource {
                $model = Post::query()->latest()->firstOrFail();

                return PostResource::loaded($model);
            },
        )
            ->withoutAbility(),

        // Global helpers must stay function calls: importing `response` as if it were a
        // class collides with `use Illuminate\Http\Response` and the file stops parsing.
        Endpoint::make(
            HttpMethod::Delete,
            uri: 'drop',
            controllerMethod: 'drop',
            callback: function (Request $request): Response {
                return response()->noContent();
            },
        )
            ->withoutAbility(),
    );
