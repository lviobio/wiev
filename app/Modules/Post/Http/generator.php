<?php
declare(strict_types=1);

use App\Enums\AuthAbilityEnum;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\DestroyPost\DestroyPostAction;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileAction;
use App\Modules\Post\Actions\Files\DetachFile\DetachFileAction;
use App\Modules\Post\Actions\Files\DownloadFile\DownloadFileAction;
use App\Modules\Post\Actions\Files\DownloadFile\DownloadFileData;
use App\Modules\Post\Actions\Files\ListFiles\ListFilesAction;
use App\Modules\Post\Actions\Files\RenameFile\RenameFileAction;
use App\Modules\Post\Actions\RestorePost\RestorePostAction;
use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Modules\Post\Actions\UpdatePost\UpdatePostAction;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Http\Controllers\PostController;
use App\Modules\Post\Http\Controllers\PostFileController;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostFileResource;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Modules\Post\VO\PostFileIdentifier;
use App\Modules\Post\VO\PostIdentifier;
use App\Support\Data\Filling\FillFromAuthenticatedUser;
use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\HttpMethod;
use App\Support\Http\Generator\Php\Gh;
use App\Support\OpenApi\SingleResourceResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

return [
    ControllerDefinition::make(PostController::class)
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
        ),

    // Файлы поста — отдельный контроллер: свой ресурс, свой блок роутов,
    // свой tag в спеке. Общая с PostController модель означает общий route
    // prefix и параметр {post}, поэтому имена роутов и tag заданы явно.
    ControllerDefinition::make(PostFileController::class)
        ->model(Post::class)
        ->resource(PostFileResource::class)
        ->routePrefix('posts')
        ->routeNamePrefix('posts.files.')
        ->tag('post-files')
        ->endpoints(
            Endpoint::make(
                HttpMethod::Get,
                Endpoint::MODEL_PARAMETER . '/files',
                controllerMethod: 'listFiles',
                routeName: 'index',
                actionClass: ListFilesAction::class,
            )
                ->ability(AuthAbilityEnum::Access, Post::class)
                ->fill(
                    new FillFromRouteParameter('id', 'post'),
                ),

            Endpoint::make(
                HttpMethod::Post,
                Endpoint::MODEL_PARAMETER . '/files',
                controllerMethod: 'attachFile',
                routeName: 'store',
                actionClass: AttachFileAction::class,
            )
                ->ability(AuthAbilityEnum::Access, Post::class)
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'post'),
                )
                // Ресурс свежесозданной модели Laravel отдаёт с 201; выведенный
                // ответ сказал бы 200, потому что эндпоинт кастомный, а не store.
                ->responses(
                    Gh::node(SingleResourceResponse::class, [
                        Gh::classRef(PostFileResource::class),
                        'response' => Gh::value('201'),
                        'description' => Gh::value('File attached'),
                    ]),
                    Gh::response(403, 'Forbidden'),
                    Gh::response(424, 'Post not found'),
                ),

            Endpoint::make(
                HttpMethod::Patch,
                Endpoint::MODEL_PARAMETER . '/files/{file}',
                controllerMethod: 'renameFile',
                routeName: 'update',
                actionClass: RenameFileAction::class,
            )
                ->ability(AuthAbilityEnum::Access, Post::class)
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'post'),
                    new FillFromRouteParameter('fileId', 'file'),
                ),

            Endpoint::make(
                HttpMethod::Delete,
                Endpoint::MODEL_PARAMETER . '/files/{file}',
                controllerMethod: 'detachFile',
                routeName: 'destroy',
                actionClass: DetachFileAction::class,
            )
                ->ability(AuthAbilityEnum::Access, Post::class)
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'post'),
                    new FillFromRouteParameter('fileId', 'file'),
                ),

            // Ответ здесь — поток, а не Resource: тип возврата метода генератор
            // выводит из Action, и StreamedResponse в эту таблицу не входит.
            // Поэтому эндпоинт-замыкание, а логика по-прежнему в Action.
            Endpoint::make(
                HttpMethod::Get,
                Endpoint::MODEL_PARAMETER . '/files/{file}/download',
                controllerMethod: 'downloadFile',
                routeName: 'download',
                callback: function (Request $request, DownloadFileAction $action): StreamedResponse {
                    $post = PostIdentifier::fromRequestParameter($request, 'post');
                    $file = PostFileIdentifier::fromRequestParameter($request, 'file');

                    $media = $action(DownloadFileData::from(['id' => $post, 'fileId' => $file]));

                    return $media->toResponse($request);
                },
            )
                ->ability(AuthAbilityEnum::Access, Post::class)
                ->responses(
                    Gh::response(200, 'File contents'),
                    Gh::response(403, 'Forbidden'),
                    Gh::response(424, 'Post not found'),
                ),
        ),
];
