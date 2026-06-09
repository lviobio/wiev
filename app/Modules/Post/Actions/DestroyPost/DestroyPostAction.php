<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\DestroyPost;

use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;

class DestroyPostAction
{
    public function __invoke(DestroyPostData $data): void
    {
        DB::transaction(static function () use ($data): void {
            Post::query()
                ->withTrashed()
                ->findOrFail($data->id->value)
                ->delete();
        });
    }
}
