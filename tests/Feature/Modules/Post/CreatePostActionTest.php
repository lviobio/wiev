<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Models\User;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;

test('create post action', function () {
    $this->actingAs($user = User::factory()->create());

    $action = resolve(CreatePostAction::class);

    $data = CreatePostData::from([
        'title' => 'Test title',
        'content' => 'Test content',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ]);

    $model = $action($data);

    expect($model)
        ->toBeInstanceOf(Post::class)
        ->and($model->authorUser)->toBe($user)
        ->and($model->title)->toBe('Test title')
        ->and($model->content)->toBe('Test content');
});