<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Actions\DestroyPost\DestroyPostAction;
use App\Modules\Post\Actions\DestroyPost\DestroyPostData;
use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Modules\Post\Actions\ShowPost\ShowPostData;
use App\Modules\Post\Actions\UpdatePost\UpdatePostAction;
use App\Modules\Post\Actions\UpdatePost\UpdatePostData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Modules\Post\VO\PostIdentifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index(PostIndexQuery $query): AnonymousResourceCollection
    {
        return PostResource::collection($query->paginate());
    }

    public function show(Request $request, ShowPostAction $action): PostResource
    {
        return PostResource::make($this->loadRelations(
            $action(ShowPostData::from([
                'actorUser' => $request->user(),
                'id' => PostIdentifier::fromRequestParameter($request, 'post'),
            ]))
        ));
    }

    public function store(CreatePostAction $action, CreatePostData $data): PostResource
    {
        return PostResource::make($this->loadRelations($action($data)));
    }

    public function update(Post $post, UpdatePostAction $action, UpdatePostData $data): PostResource
    {
        return $this->resource($action($post, $data));
    }

    public function destroy(Request $request, DestroyPostAction $action): Response
    {
        $action(DestroyPostData::from([
            'actorUser' => $request->user(),
            'id' => PostIdentifier::fromRequestParameter($request, 'post'),
        ]));

        return response()->noContent();
    }

    private function resource(Post $post): PostResource
    {
        return PostResource::make($this->loadRelations($post));
    }

    private function loadRelations(Post $model): Post
    {
        return $model->load([
            'authorUser',
            'media' => fn($q) => $q->whereCollectionName(PostMediaCollectionEnum::Cover->value),
        ]);
    }
}
