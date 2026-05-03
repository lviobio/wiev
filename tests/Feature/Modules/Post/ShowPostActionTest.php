<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Modules\Post\Actions\ShowPost\ShowPostData;
use App\Modules\Post\Models\Post;

test('show post action', function () {
    $recent = Post::factory()->create([
        'title' => 'Test title',
        'content' => 'Test content',
    ]);

    $this->actingAs($user = $recent->authorUser);

    $action = resolve(ShowPostAction::class);

    $data = ShowPostData::from([
        'id' => $recent->getKey(),
    ]);

    $model = $action($data);

    expect($model)
        ->toBeInstanceOf(Post::class)
        ->and($model->authorUser->is($user))->toBeTrue()
        ->and($model->title)->toBe('Test title')
        ->and($model->content)->toBe('Test content');
});