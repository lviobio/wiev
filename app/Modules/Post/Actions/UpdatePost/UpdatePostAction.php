<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;

readonly class UpdatePostAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(UpdatePostData $data): Post
    {
        $model = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->findOrFail($data->id),
        );

        Gate::forUser($data->actorUser)->authorize('update', $model);

        $this->execute(new PostEntity($model), $data);

        $this->modelManager->flush();

        return $model;
    }

    private function execute(PostEntity $post, UpdatePostData $data): void
    {
        $post->setTitle($data->title);
        $post->setContent($data->content);

        if (!$data->cover instanceof Optional) {
            $post->setCover($data->cover);
        }
    }
}
