<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\DetachFile;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

readonly class DetachFileAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(DetachFileData $data): void
    {
        $post = $this->modelManager->retrieve(
            Post::class,
            static fn(Builder|Post $query): Post => $query->findOrFail($data->id),
        );

        Gate::forUser($data->actorUser)->authorize('update', $post);

        // Медиа-модель проекта мягко удаляемая, то есть удаление — обычный
        // обратимый UPDATE. Ему место внутри транзакции flush(), а не после
        // коммита, поэтому строка отдаётся менеджеру, а не media library.
        $this->modelManager->remove(new PostEntity($post)->file($data->fileId));

        $this->modelManager->flush();
    }
}
