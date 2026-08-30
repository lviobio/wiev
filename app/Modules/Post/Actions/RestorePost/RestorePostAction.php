<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\RestorePost;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

readonly class RestorePostAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(RestorePostData $data): Post
    {
        $model = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->withTrashed()->findOrFail($data->id->value),
        );

        Gate::forUser($data->actorUser)->authorize('restore', $model);

        $this->modelManager->restore($model);
        $this->modelManager->flush();

        return $model;
    }
}
