<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdatePostAction
{
    public function __invoke(UpdatePostData $data): Post
    {
        return DB::transaction(static function () use ($data): Post {
            $model = Post::query()->findOrFail($data->id->value);

            $model->update($data->except('id', 'actorUser', 'cover')->toArray());

            if (!$data->cover instanceof Optional) {
                if ($data->cover) {
                    $model->addMedia($data->cover)->toMediaCollection(PostMediaCollectionEnum::Cover->value);
                } else {
                    $model->clearMediaCollection(PostMediaCollectionEnum::Cover->value);
                }
            }

            return $model;
        });
    }
}
