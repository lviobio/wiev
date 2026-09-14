<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Files;

use App\Core\ModelManager\ModelManagerContract;
use App\Models\Media;
use App\Models\User;
use App\Modules\Post\Actions\Files\DetachFile\DetachFileAction;
use App\Modules\Post\Actions\Files\DetachFile\DetachFileData;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Domain\VO\PostFileIdentifier;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->model = Post::factory()->create();
    $this->author = $this->model->authorUser;
    $this->file = $this->model
        ->addMedia(UploadedFile::fake()->create('report.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    $this->actingAs($this->author);
});

test('detach file action', function () {
    $path = $this->file->getPath();

    resolve(DetachFileAction::class)(DetachFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $this->file->uuid,
        'actorUser' => $this->author,
    ]));

    // медиа-модель мягко удаляемая: строка уходит из выборки, файл остаётся
    expect(Media::query()->count())->toBe(0)
        ->and(Media::withTrashed()->count())->toBe(1)
        ->and($this->model->fresh()->mediaFiles()->count())->toBe(0)
        ->and(file_exists($path))->toBeTrue();
});

test('detaching a file happens on flush', function () {
    $manager = resolve(ModelManagerContract::class);

    $post = $manager->retrieve(
        Post::class,
        fn(Builder $query): Post => $query->findOrFail($this->model->getKey()),
    );

    $manager->remove(new PostEntity($post)->file(PostFileIdentifier::make($this->file->uuid)));

    expect(Media::query()->count())->toBe(1);

    $manager->flush();

    expect(Media::query()->count())->toBe(0);
});

test('detach file action forbids a stranger', function () {
    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    resolve(DetachFileAction::class)(DetachFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $this->file->uuid,
        'actorUser' => $stranger,
    ]));
})->throws(AuthorizationException::class);

test('detach file action fails on a file of another post', function () {
    $another = Post::factory()->create();
    $foreign = $another->addMedia(UploadedFile::fake()->create('foreign.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    resolve(DetachFileAction::class)(DetachFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $foreign->uuid,
        'actorUser' => $this->author,
    ]));
})->throws(ModelNotFoundException::class);

test('a detached file is not listed among the files of the post', function () {
    resolve(DetachFileAction::class)(DetachFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $this->file->uuid,
        'actorUser' => $this->author,
    ]));

    expect(new PostEntity($this->model->fresh())->files())->toBeEmpty();
});
