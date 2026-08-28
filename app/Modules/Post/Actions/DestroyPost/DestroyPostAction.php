<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\DestroyPost;

use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DestroyPostAction
{
    public function __invoke(DestroyPostData $data): Post
    {
        return DB::transaction(static function () use ($data): Post {
            $model = Post::query()
                ->withTrashed()
                ->findOrFail($data->id);

            Gate::forUser($data->actorUser)->authorize('delete', $model);

            $model->delete();

            return $model;
        });
    }
}
