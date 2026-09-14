<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\DownloadFile;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

readonly class DownloadFileAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    /**
     * Возвращает саму запись: превращение её в поток — забота HTTP-слоя,
     * а не домена.
     */
    public function __invoke(DownloadFileData $data): Media
    {
        $post = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->findOrFail($data->id),
        );

        return new PostEntity($post)->file($data->fileId);
    }
}
