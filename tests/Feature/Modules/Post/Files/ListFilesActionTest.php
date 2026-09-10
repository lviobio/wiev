<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Files;

use App\Modules\Post\Actions\Files\ListFiles\ListFilesAction;
use App\Modules\Post\Actions\Files\ListFiles\ListFilesData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->model = Post::factory()->create();
});

test('list files action', function () {
    $this->model->addMedia(UploadedFile::fake()->create('first.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);
    $this->model->addMedia(UploadedFile::fake()->create('second.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    $files = resolve(ListFilesAction::class)(ListFilesData::from(['id' => $this->model->getKey()]));

    expect($files)->toHaveCount(2)
        ->and($files->pluck('file_name')->all())->toBe(['first.pdf', 'second.pdf']);
});

test('list files action leaves the cover out of it', function () {
    $this->model->addMedia(UploadedFile::fake()->image('cover.jpg'))
        ->toMediaCollection(PostMediaCollectionEnum::Cover->value);
    $this->model->addMedia(UploadedFile::fake()->create('report.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    $files = resolve(ListFilesAction::class)(ListFilesData::from(['id' => $this->model->getKey()]));

    // связь mediaFiles сужена по collection_name, обложка в неё попасть не должна
    expect($files)->toHaveCount(1)
        ->and($files->first()->file_name)->toBe('report.pdf')
        ->and($this->model->fresh()->media()->count())->toBe(2);
});

test('list files action returns nothing for a post without files', function () {
    expect(resolve(ListFilesAction::class)(ListFilesData::from(['id' => $this->model->getKey()])))
        ->toBeEmpty();
});
