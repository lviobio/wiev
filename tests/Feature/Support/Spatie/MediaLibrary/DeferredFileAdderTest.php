<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Spatie\MediaLibrary;

use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->manager = resolve(ModelManagerContract::class);
});

function cover(): UploadedFile
{
    return UploadedFile::fake()->image('cover.jpg');
}

function collectionName(): string
{
    return PostMediaCollectionEnum::Cover->value;
}

test('media of a managed model is written on flush, not on toMediaCollection', function () {
    $model = Post::factory()->create();

    $managed = $this->manager->retrieve(
        Post::class,
        static fn(Builder $query): Post => $query->findOrFail($model->getKey()),
    );

    $managed->addMedia(cover())->toMediaCollection(collectionName());

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(0);

    $this->manager->flush();

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(1);
});

test('media of a new model is written on flush too', function () {
    $model = new Post;
    $model->title = 'Deferred';
    $model->content = null;
    $model->authorUser()->associate(\App\Models\User::factory()->create());

    $model->addMedia(cover())->toMediaCollection(collectionName());

    $this->manager->persist($model);

    expect(Post::query()->count())->toBe(0);

    $this->manager->flush();

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(1);
});

test('media of a model outside the manager is still written immediately', function () {
    $model = Post::factory()->create();

    $model->addMedia(cover())->toMediaCollection(collectionName());

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(1);
});

test('clearing a collection is deferred to flush for a managed model', function () {
    $model = Post::factory()->create();
    $model->addMedia(cover())->toMediaCollection(collectionName());

    $managed = $this->manager->retrieve(
        Post::class,
        static fn(Builder $query): Post => $query->findOrFail($model->getKey()),
    );

    $managed->clearMediaCollectionOnFlush(collectionName());

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(1);

    $this->manager->flush();

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(0);
});

test('a deferred clear outside the manager still happens on save', function () {
    $model = Post::factory()->create();
    $model->addMedia(cover())->toMediaCollection(collectionName());

    $model->clearMediaCollectionOnFlush(collectionName());
    $model->title = 'Saved without the manager';
    $model->save();

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(0);
});

test('nothing is written when the flush fails', function () {
    $model = Post::factory()->create();

    $managed = $this->manager->retrieve(
        Post::class,
        static fn(Builder $query): Post => $query->findOrFail($model->getKey()),
    );

    $managed->addMedia(cover())->toMediaCollection(collectionName());
    // Несуществующая колонка — падаем на записи самой модели, до колбэков.
    $managed->setAttribute('no_such_column', 'boom');

    expect(fn() => $this->manager->flush())->toThrow(QueryException::class);

    expect($model->fresh()->getMedia(collectionName()))->toHaveCount(0);
});
