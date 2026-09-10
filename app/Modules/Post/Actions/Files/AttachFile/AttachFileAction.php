<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\AttachFile;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

readonly class AttachFileAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(AttachFileData $data): Media
    {
        $post = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->findOrFail($data->id->value),
        );

        Gate::forUser($data->actorUser)->authorize('update', $post);

        // Запись отложена до коммита, но заполнится этот же экземпляр —
        // к моменту возврата у него уже есть id и uuid.
        $media = new PostEntity($post)->attachFile($data->file);

        $this->modelManager->flush();

        return $media;
    }
}
