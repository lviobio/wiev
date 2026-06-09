<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

class UpdatePostAction
{
    public function __invoke(Post $model, UpdatePostData $data): Post
    {
        return DB::transaction(static function () use ($model, $data): Post {
            $model->update($data->except('cover', 'actorUser')->toArray());

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
