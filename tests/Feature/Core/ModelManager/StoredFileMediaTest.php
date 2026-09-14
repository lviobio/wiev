<?php
declare(strict_types=1);

namespace Tests\Feature\Core\ModelManager;

use App\Core\Upload\Models\TemporaryUpload;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileAction;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileData;
use App\Modules\Post\Domain\VO\PostFile;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\Storage;

/**
 * The point of addressing a stored file by disk + path: the staging disk and the
 * media disk can be entirely separate (different buckets, different drivers), and
 * turning a staged file into media still just works - copied over, original removed.
 */
beforeEach(function () {
    config(['uploads.disk' => 'staging']);
    Storage::fake('staging');
    Storage::fake('public');
});

test('a stored file on one disk becomes media on another, and the original is removed', function () {
    $post = Post::factory()->create();
    $upload = TemporaryUpload::factory()->create([
        'user_id' => $post->authorUser->getKey(),
        'original_name' => 'contract draft.pdf',
    ]);

    expect($upload->disk)->toBe('staging')
        ->and(Storage::disk('staging')->exists($upload->path))->toBeTrue();

    $media = $post->addMedia(new PostFile(
        originalName: $upload->original_name,
        mimeType: $upload->mime_type,
        size: $upload->size,
        disk: $upload->disk,
        path: $upload->path,
    ))->toMediaCollection(PostMediaCollectionEnum::Files->value);

    expect($media->disk)->toBe('public')
        ->and($media->file_name)->toBe('contract-draft.pdf')
        ->and($media->name)->toBe('contract draft')
        ->and(Storage::disk('public')->exists($media->getPathRelativeToRoot()))->toBeTrue()
        ->and(Storage::disk('staging')->exists($upload->path))->toBeFalse();
});

test('the same holds through the action, with the write deferred to flush', function () {
    $post = Post::factory()->create();
    $this->actingAs($post->authorUser);
    $upload = TemporaryUpload::factory()->create(['user_id' => $post->authorUser->getKey()]);

    $media = resolve(AttachFileAction::class)(AttachFileData::from([
        'id' => $post->getKey(),
        'actorUser' => $post->authorUser,
        'file' => $upload->uuid,
    ]));

    expect($media->exists)->toBeTrue()
        ->and($media->disk)->toBe('public')
        ->and(Storage::disk('public')->exists($media->getPathRelativeToRoot()))->toBeTrue()
        ->and(Storage::disk('staging')->exists($upload->path))->toBeFalse()
        ->and($upload->fresh()->used_at)->not->toBeNull();
});
