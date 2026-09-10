<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Files;

use App\Models\User;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileAction;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');

    $this->model = Post::factory()->create();
    $this->author = $this->model->authorUser;
});

test('attach file action', function () {
    $this->actingAs($this->author);

    $media = resolve(AttachFileAction::class)(AttachFileData::from([
        'id' => $this->model->getKey(),
        'actorUser' => $this->author,
        'file' => UploadedFile::fake()->create('contract draft.pdf', 12),
    ]));

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->exists)->toBeTrue()
        ->and($media->uuid)->not->toBeNull()
        ->and($media->collection_name)->toBe(PostMediaCollectionEnum::Files->value)
        ->and($media->file_name)->toBe('contract-draft.pdf')
        ->and($media->name)->toBe('contract draft')
        ->and($this->model->fresh()->mediaFiles()->count())->toBe(1);
});

test('attach file action forbids a stranger', function () {
    $stranger = User::factory()->create();
    $this->actingAs($stranger);

    resolve(AttachFileAction::class)(AttachFileData::from([
        'id' => $this->model->getKey(),
        'actorUser' => $stranger,
        'file' => UploadedFile::fake()->create('doc.pdf', 1),
    ]));
})->throws(AuthorizationException::class);
