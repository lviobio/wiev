<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Actions\RemoveCover\RemoveCoverAction;
use App\Modules\Post\Actions\RemoveCover\RemoveCoverData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;

test('remove cover action', function () {
    $model = Post::factory()->create();
    $model->addMedia(UploadedFile::fake()->image('cover.jpg'))
        ->toMediaCollection(PostMediaCollectionEnum::Cover->value);

    $this->actingAs($user = $model->authorUser);

    expect($model->getMedia(PostMediaCollectionEnum::Cover->value))->toHaveCount(1);

    $action = resolve(RemoveCoverAction::class);

    $action(RemoveCoverData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
    ]));

    expect($model->fresh()->getMedia(PostMediaCollectionEnum::Cover->value))->toBeEmpty();
});

test('remove cover action leaves a post without a cover alone', function () {
    $model = Post::factory()->create();

    $this->actingAs($user = $model->authorUser);

    $action = resolve(RemoveCoverAction::class);

    $action(RemoveCoverData::from([
        'id' => $model->getKey(),
        'actorUser' => $user,
    ]));

    expect($model->fresh()->getMedia(PostMediaCollectionEnum::Cover->value))->toBeEmpty();
});
