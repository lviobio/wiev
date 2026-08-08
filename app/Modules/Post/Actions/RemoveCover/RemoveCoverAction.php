<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\RemoveCover;

use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\DB;

class RemoveCoverAction
{
    public function __invoke(RemoveCoverData $data): void
    {
        DB::transaction(static function () use ($data): void {
            Post::query()
                ->findOrFail($data->id->value)
                ->clearMediaCollection(PostMediaCollectionEnum::Cover->value);
        });
    }
}
