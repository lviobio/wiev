<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\RestorePost;

use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;

class RestorePostAction
{
    public function __invoke(RestorePostData $data): Post
    {
        return DB::transaction(static function () use ($data): Post {
            $model = Post::query()
                ->withTrashed()
                ->findOrFail($data->id->value);

            $model->restore();

            return $model;
        });
    }
}
