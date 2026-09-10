<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\RenameFile;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

readonly class RenameFileAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(RenameFileData $data): Media
    {
        $post = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->findOrFail($data->id->value),
        );

        Gate::forUser($data->actorUser)->authorize('update', $post);

        $media = new PostEntity($post)->renameFile($data->fileId, $data->name);

        // Строка media в графе поста не состоит, поэтому берётся под управление явно.
        $this->modelManager->persist($media);
        $this->modelManager->flush();

        return $media;
    }
}
