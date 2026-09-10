<?php
declare(strict_types=1);

use App\Enums\AuthAbilityEnum;
use App\Modules\Post\Actions\Files\ListFiles\ListFilesAction;
use App\Modules\Post\Http\Controllers\PostFileController;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostFileResource;
use App\Modules\Post\Models\Post;
use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\HttpMethod;

return ControllerDefinition::make(PostFileController::class)
    ->model(Post::class)
    ->resource(PostFileResource::class)
    ->endpoints(
        // Коллекция приходит из Action — пагинировать нечего.
        Endpoint::make(
            HttpMethod::Get,
            Endpoint::MODEL_PARAMETER . '/files',
            controllerMethod: 'listFiles',
            actionClass: ListFilesAction::class,
        )
            ->ability(AuthAbilityEnum::Access, Post::class)
            ->fill(
                new FillFromRouteParameter('id', 'post'),
            ),

        // Коллекция приходит из Query — пагинируется.
        Endpoint::index(PostIndexQuery::class)
            ->ability(AuthAbilityEnum::Access, Post::class),
    );
