<?php
declare(strict_types=1);

namespace App\Modules\Post\Services;

use App\Modules\Post\Data\PostUpdateData;
use App\Modules\Post\Models\Post;
use Spatie\LaravelData\Optional;

class PostService
{
    public function update(Post $model, PostUpdateData $data): void
    {
        $model->update($data->except('cover', 'actorUser')->toArray());

        if (!$data->cover instanceof Optional) {
            if ($data->cover) {
                $model->addMedia($data->cover)->toMediaCollection(Post::MEDIA_COLLECTION_COVER);
            } else {
                $model->clearMediaCollection(Post::MEDIA_COLLECTION_COVER);
            }
        }

        $model->save();
    }
}
