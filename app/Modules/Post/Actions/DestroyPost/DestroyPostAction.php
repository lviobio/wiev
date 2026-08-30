<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\DestroyPost;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

readonly class DestroyPostAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(DestroyPostData $data): void
    {
        $model = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->withTrashed()->findOrFail($data->id->value),
        );

        Gate::forUser($data->actorUser)->authorize('delete', $model);

        $this->modelManager->remove($model);
        $this->modelManager->flush();
    }
}
