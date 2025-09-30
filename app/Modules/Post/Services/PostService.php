<?php
declare(strict_types=1);

namespace App\Modules\Post\Services;

use App\Modules\Post\Data\PostCreateData;
use App\Modules\Post\Data\PostUpdateData;
use App\Modules\Post\Models\Post;

class PostService
{
    public function create(PostCreateData $data): Post
    {
        $model = new Post($data->except('cover', 'author')->toArray());
        $model->authorUser()->associate($data->author);

        if ($data->cover) {
            $model->addMedia($data->cover)->toMediaCollection(Post::MEDIA_COLLECTION_COVER);
        }

        $model->save();

        return $model;
    }

    public function update(Post $model, PostUpdateData $data): void
    {
        $model->update($data->except('cover', 'operatingUser')->toArray());

        if ($data->cover) {
            $model->addMedia($data->cover)->toMediaCollection(Post::MEDIA_COLLECTION_COVER);
        }

        $model->save();
    }
}
