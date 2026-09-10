<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Files;

use App\Modules\Post\Actions\Files\RenameFile\RenameFileAction;
use App\Modules\Post\Actions\Files\RenameFile\RenameFileData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('public');

    $this->model = Post::factory()->create();
    $this->author = $this->model->authorUser;
    $this->file = $this->model
        ->addMedia(UploadedFile::fake()->create('report.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    $this->actingAs($this->author);
});

test('rename file action', function () {
    $media = resolve(RenameFileAction::class)(RenameFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $this->file->uuid,
        'actorUser' => $this->author,
        'name' => 'Annual report',
    ]));

    // меняется отображаемое имя, файл на диске остаётся на месте
    expect($media->name)->toBe('Annual report')
        ->and($this->file->fresh()->name)->toBe('Annual report')
        ->and($this->file->fresh()->file_name)->toBe('report.pdf');
});

test('rename file action does not find a file of another post', function () {
    $another = Post::factory()->create();
    $foreign = $another->addMedia(UploadedFile::fake()->create('foreign.pdf', 1))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    resolve(RenameFileAction::class)(RenameFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => $foreign->uuid,
        'actorUser' => $this->author,
        'name' => 'Hijacked',
    ]));
})->throws(ModelNotFoundException::class);

test('rename file action fails on an unknown file', function () {
    resolve(RenameFileAction::class)(RenameFileData::from([
        'id' => $this->model->getKey(),
        'fileId' => Str::uuid()->toString(),
        'actorUser' => $this->author,
        'name' => 'Nothing',
    ]));
})->throws(ModelNotFoundException::class);
