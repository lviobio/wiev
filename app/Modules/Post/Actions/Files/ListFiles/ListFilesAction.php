<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\ListFiles;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

readonly class ListFilesAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    /**
     * Чтение политикой не закрыто — как и сам пост.
     *
     * @return Collection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media>
     */
    public function __invoke(ListFilesData $data): Collection
    {
        $post = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->findOrFail($data->id),
        );

        return new PostEntity($post)->files();
    }
}
