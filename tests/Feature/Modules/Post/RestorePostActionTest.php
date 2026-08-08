<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Actions\RestorePost\RestorePostAction;
use App\Modules\Post\Actions\RestorePost\RestorePostData;
use App\Modules\Post\Models\Post;

test('restore post action', function () {
    $model = Post::factory()->create();
    $model->delete();

    $this->actingAs($user = $model->authorUser);

    expect($model->fresh()->trashed())->toBeTrue();

    $action = resolve(RestorePostAction::class);

    $restored = $action(RestorePostData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
    ]));

    expect($restored)->toBeInstanceOf(Post::class)
        ->and($restored->trashed())->toBeFalse()
        ->and($model->fresh()->trashed())->toBeFalse();
});

test('restore post action leaves a live post alone', function () {
    $model = Post::factory()->create();

    $this->actingAs($user = $model->authorUser);

    $action = resolve(RestorePostAction::class);

    $restored = $action(RestorePostData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
    ]));

    expect($restored->trashed())->toBeFalse();
});
