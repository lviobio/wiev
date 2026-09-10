<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Files;

use App\Modules\Post\Actions\Files\DownloadFile\DownloadFileAction;
use App\Modules\Post\Actions\Files\DownloadFile\DownloadFileData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');

    $this->model = Post::factory()->create();
    $this->file = $this->model
        ->addMedia(UploadedFile::fake()->create('report.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);
});

test('download file action', function () {
    $media = resolve(DownloadFileAction::class)(DownloadFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $this->file->uuid,
    ]));

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->getKey())->toBe($this->file->getKey())
        ->and(file_exists($media->getPath()))->toBeTrue();
});

test('download file action fails on an unknown file', function () {
    resolve(DownloadFileAction::class)(DownloadFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => Str::uuid()->toString(),
    ]));
})->throws(ModelNotFoundException::class);
