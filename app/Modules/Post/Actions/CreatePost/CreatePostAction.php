<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Domain\Data\NewPostData;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Models\Post;

readonly class CreatePostAction
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    public function __invoke(CreatePostData $data): Post
    {
        $entity = PostEntity::makeNew(new NewPostData(
            title: $data->title,
            content: $data->content,
            cover: $data->cover,
            author: $data->authorUser,
        ));

        $model = $entity->toModel();

        $this->modelManager->persist($model);
        $this->modelManager->flush();

        return $model;
    }
}
