<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Core\Upload\Models\TemporaryUpload;
use App\Models\User;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\Storage;

test('create post action', function () {
    Storage::fake('public');
    Storage::fake(config('uploads.disk'));

    $this->actingAs($user = User::factory()->create());
    $cover = TemporaryUpload::factory()->image()->create(['user_id' => $user->getKey()]);

    $action = resolve(CreatePostAction::class);

    $data = CreatePostData::from([
        'title' => 'Test title',
        'content' => 'Test content',
        'cover' => $cover->uuid,
        'authorUser' => $user,
    ]);

    $model = $action($data);

    expect($model)
        ->toBeInstanceOf(Post::class)
        ->and($model->authorUser)->toBe($user)
        ->and($model->title)->toBe('Test title')
        ->and($model->content)->toBe('Test content')
        ->and($model->fresh()->getMedia(PostMediaCollectionEnum::Cover->value))->toHaveCount(1);
});