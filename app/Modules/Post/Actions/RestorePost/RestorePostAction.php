<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\RestorePost;

use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RestorePostAction
{
    public function __invoke(RestorePostData $data): Post
    {
        return DB::transaction(static function () use ($data): Post {
            $model = Post::query()
                ->withTrashed()
                ->findOrFail($data->id->value);

            Gate::forUser($data->actorUser)->authorize('restore', $model);

            $model->restore();

            return $model;
        });
    }
}
