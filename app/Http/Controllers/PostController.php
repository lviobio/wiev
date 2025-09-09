<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Posts\DestroyPostRequest;
use App\Http\Requests\Posts\IndexPostRequest;
use App\Http\Requests\Posts\ShowPostRequest;
use App\Http\Requests\Posts\StorePostRequest;
use App\Http\Requests\Posts\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        $query = Post::query()
            ->orderBy('id')
            ->tap($this->eagerLoad(...));

        $validated = $request->safe();
        $validated->whenFilled('filters', function (array $filters) use ($query) {
            if (!empty($filters['search'])) {
                $query->where(fn($q) => $q
                    ->whereLike('title', '%' . $filters['search'] . '%')
                    ->orWhereLike('content', '%' . $filters['search'] . '%')
                );
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

    public function show(ShowPostRequest $request): PostResource
    {
        $model = $request->model;

        return $this->resource($model);
    }

    public function store(StorePostRequest $request): PostResource
    {
        $model = Post::create($request->validated());

        return $this->resource($model);
    }

    public function update(UpdatePostRequest $request): PostResource
    {
        $model = $request->model;
        $model->update($request->validated());

        return $this->resource($model);
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
            'authorUser'
        ]);

        if (!$model instanceof Builder) {
            $query->eagerLoadRelations([$model]);
        }

        return $model;
    }
}
