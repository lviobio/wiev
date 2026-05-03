<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;

class CreatePostAction
{
    public function __invoke(CreatePostData $data): Post
    {
        return DB::transaction(static function () use ($data) {
            $model = new Post($data->only('title', 'content')->toArray());

            $model->authorUser()->associate($data->author);

            if ($data->cover) {
                $model->addMedia($data->cover)->toMediaCollection(Post::MEDIA_COLLECTION_COVER);
            }

            $model->save();

            return $model;
        });
    }
}