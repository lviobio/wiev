<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Modules\Post\Actions\ShowPost\ShowPostData;
use App\Modules\Post\Data\PostUpdateData;
use App\Modules\Post\Http\Requests\DestroyPostRequest;
use App\Modules\Post\Http\Requests\IndexPostRequest;
use App\Modules\Post\Http\Resources\PostResource;
use App\Modules\Post\Models\Post;
use App\Modules\Post\Services\PostService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    )
    {
    }

    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        $query = Post::query()
            ->orderBy('id')
            ->tap($this->eagerLoad(...));

        $validated = $request->safe();
        if ($validated->has('search')) {
            $search = '%' . $validated->string('search') . '%';
            $query->where(fn($q) => $q
                ->whereLike('title', $search)
                ->orWhereLike('content', $search)
            );
        }

        $validated->whenFilled('filters', function (array $filters) use ($query) {
            if (!empty($filters['title'])) {
                $query->whereLike('title', '%' . $filters['title'] . '%');
            }

            if (!empty($filters['trashed'])) {
                match ($filters['trashed']) {
                    'with' => $query->withTrashed(),
                    'only' => $query->onlyTrashed(),
                };
            }
        });

        $paginator = $query->paginate(perPage: $validated->integer('per_page'), page: $validated->integer('page'));

        return PostResource::collection($paginator);
    }

    public function show(ShowPostAction $action, ShowPostData $data): PostResource
    {
        return $this->resource($action($data));
    }

    public function store(CreatePostAction $action, CreatePostData $data): PostResource
    {
        return $this->resource($action($data));
    }

    public function update(PostUpdateData $data, Post $post): PostResource
    {
        $this->postService->update($post, $data);

        return $this->resource($post);
    }

    public function destroy(DestroyPostRequest $request): Response
    {
        $model = $request->model;
        $model->delete();

        return response()->noContent();
    }

    private function resource(Post $model): PostResource
    {
        return new PostResource(tap($model, $this->eagerLoad(...)));
    }

    private function eagerLoad(Post|Builder $model): Post|Builder
    {
        $query = $model->with([
            'authorUser',
            'media' => fn(Builder|MorphMany|Media $q) => $q->whereCollectionName(Post::MEDIA_COLLECTION_COVER),
        ]);

        if (!$model instanceof Builder) {
            $query->eagerLoadRelations([$model]);
        }

        return $model;
    }
}
