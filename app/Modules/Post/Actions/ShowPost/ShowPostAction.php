<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\ShowPost;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;

readonly class ShowPostAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(ShowPostData $data): Post
    {
        return $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->withTrashed()->findOrFail($data->id->value),
        );
    }
}
