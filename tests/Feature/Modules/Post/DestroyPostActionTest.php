<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Actions\DestroyPost\DestroyPostAction;
use App\Modules\Post\Actions\DestroyPost\DestroyPostData;
use App\Modules\Post\Models\Post;

test('destroy post action', function () {
    $model = Post::factory()->create();

    $this->actingAs($user = $model->authorUser);

    $action = resolve(DestroyPostAction::class);

    $action(DestroyPostData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
    ]));

    expect($model->fresh()->trashed())->toBeTrue();
});
