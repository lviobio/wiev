<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;

class CreatePostAction
{
    public function __invoke(CreatePostData $data): Post
    {
        return DB::transaction(static function () use ($data) {
            $model = new Post;

            $model->title = $data->title;
            $model->content = $data->content;

            $model->authorUser()->associate($data->authorUser);

            if ($data->cover) {
                $model->addMedia($data->cover)->toMediaCollection(PostMediaCollectionEnum::Cover->value);
            }

            $model->save();

            return $model;
        });
    }
}