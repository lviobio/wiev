<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\ShowPost;

use App\Modules\Post\Models\Post;

class ShowPostAction
{
    public function handle(ShowPostData $data): Post
    {
        return Post::query()
            ->withTrashed()
            ->findOrFail($data->id->value);
    }
}