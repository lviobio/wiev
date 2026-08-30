<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Spatie\Data;

use App\Models\User;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Actions\UpdatePost\UpdatePostData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\VO\PostCover;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Optional;

test('an uploaded file becomes a domain value', function () {
    $data = CreatePostData::from([
        'title' => 'Test title',
        'content' => null,
        'cover' => UploadedFile::fake()->image('my photo.jpg'),
        'authorUser' => User::factory()->create(),
    ]);

    expect($data->cover)->toBeInstanceOf(PostCover::class)
        ->and($data->cover->originalName)->toBe('my photo.jpg')
        ->and($data->cover->mimeType)->toBe('image/jpeg')
        ->and($data->cover->size)->toBeGreaterThan(0)
        ->and(file_exists($data->cover->path))->toBeTrue();
});

test('an absent cover stays absent', function () {
    $withNull = CreatePostData::from([
        'title' => 'Test title',
        'content' => null,
        'cover' => null,
        'authorUser' => User::factory()->create(),
    ]);

    $withoutKey = UpdatePostData::from([
        'title' => 'Test title',
        'content' => null,
        'actorUser' => $user = User::factory()->create(),
        'id' => 1,
    ]);

    expect($withNull->cover)->toBeNull()
        ->and($withoutKey->cover)->toBeInstanceOf(Optional::class);
});

test('the original file name survives the trip to media library', function () {
    Storage::fake('public');

    $data = CreatePostData::from([
        'title' => 'Test title',
        'content' => null,
        'cover' => UploadedFile::fake()->image('my photo.jpg'),
        'authorUser' => User::factory()->create(),
    ]);

    $model = resolve(CreatePostAction::class)($data);

    $media = $model->fresh()->getFirstMedia(PostMediaCollectionEnum::Cover->value);

    // без переходника media library вывела бы имя из временного пути (phpXXXX)
    expect($media->file_name)->toBe('my-photo.jpg')
        ->and($media->name)->toBe('my photo');
});

test('the cover is validated as an image', function () {
    $rules = CreatePostData::getValidationRules([]);

    expect($rules['cover'])->toContain('image')
        ->and($rules['cover'])->toContain('max:10240');
});
