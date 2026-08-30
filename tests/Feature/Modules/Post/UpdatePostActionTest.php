<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Models\User;
use App\Modules\Post\Actions\UpdatePost\UpdatePostAction;
use App\Modules\Post\Actions\UpdatePost\UpdatePostData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;

test('update post action', function () {
    $model = Post::factory()->create();
    $this->actingAs($user = $model->authorUser);

    $action = resolve(UpdatePostAction::class);

    $updated = $action(UpdatePostData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
        'title' => 'Updated title',
        'content' => 'Updated content',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ]));

    expect($updated->title)->toBe('Updated title')
        ->and($model->fresh()->title)->toBe('Updated title')
        ->and($model->fresh()->content)->toBe('Updated content')
        ->and($updated->getMedia(PostMediaCollectionEnum::Cover->value))->toHaveCount(1);
});

test('update post action clears the cover', function () {
    $model = Post::factory()->create();
    $model->addMedia(UploadedFile::fake()->image('cover.jpg'))
        ->toMediaCollection(PostMediaCollectionEnum::Cover->value);

    $this->actingAs($user = $model->authorUser);

    $updated = resolve(UpdatePostAction::class)(UpdatePostData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
        'title' => 'Kept title',
        'content' => null,
        'cover' => null,
    ]));

    expect($updated->refresh()->getMedia(PostMediaCollectionEnum::Cover->value))->toHaveCount(0);
});

test('update post action forbids a stranger', function () {
    $model = Post::factory()->create();

    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    resolve(UpdatePostAction::class)(UpdatePostData::from([
        'id' => $model->getKey(),
        'actorUser' => $stranger,
        'title' => 'Hacked',
        'content' => null,
        'cover' => null,
    ]));
})->throws(AuthorizationException::class);
