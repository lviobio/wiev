<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\ModelManager\ModelManagerContract;
use App\Core\Upload\Exceptions\TemporaryUploadAlreadyUsedException;
use App\Core\Upload\Models\TemporaryUpload;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileData;
use App\Modules\Post\Domain\PostEntity;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use App\Modules\Post\VO\PostFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * "Used" means "the changes this file was needed for are committed" - nothing less,
 * nothing earlier. See App\Core\Upload\TemporaryUploadClaimer.
 */
beforeEach(function () {
    Storage::fake('public');
    Storage::fake(config('uploads.disk'));

    $this->post = Post::factory()->create();
    $this->actingAs($this->post->authorUser);
    $this->upload = TemporaryUpload::factory()->create(['user_id' => $this->post->authorUser->getKey()]);
    $this->manager = resolve(ModelManagerContract::class);
});

function fileValueOf(TemporaryUpload $upload): PostFile
{
    return new PostFile(
        originalName: $upload->original_name,
        mimeType: $upload->mime_type,
        size: $upload->size,
        disk: $upload->disk,
        path: $upload->path,
    );
}

test('building the value does not claim the upload', function () {
    AttachFileData::from([
        'id' => $this->post->getKey(),
        'actorUser' => $this->post->authorUser,
        'file' => $this->upload->uuid,
    ]);

    expect($this->upload->fresh()->used_at)->toBeNull();
});

test('registering the media does not claim the upload either - only flush does', function () {
    $post = $this->manager->retrieve(Post::class, fn(Builder $q) => $q->findOrFail($this->post->getKey()));

    new PostEntity($post)->attachFile(fileValueOf($this->upload));

    // A request that dies here - dd(), fatal, kill - leaves nothing to undo.
    expect($this->upload->fresh()->used_at)->toBeNull()
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeTrue();

    $this->manager->flush();

    expect($this->upload->fresh()->used_at)->not->toBeNull()
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeFalse()
        ->and($post->fresh()->mediaFiles()->count())->toBe(1);
});

test('a rolled-back flush leaves the upload unclaimed and the file in place', function () {
    $post = $this->manager->retrieve(Post::class, fn(Builder $q) => $q->findOrFail($this->post->getKey()));
    new PostEntity($post)->attachFile(fileValueOf($this->upload));

    // Runs after the claim, still inside the transaction.
    $this->manager->beforeCommit(fn() => throw new RuntimeException('something else in the unit of work failed'));

    expect(fn() => $this->manager->flush())->toThrow(RuntimeException::class)
        ->and($this->upload->fresh()->used_at)->toBeNull()
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeTrue()
        ->and($post->fresh()->mediaFiles()->count())->toBe(0);
});

test('losing the claim to another request rolls the whole flush back', function () {
    $post = $this->manager->retrieve(Post::class, fn(Builder $q) => $q->findOrFail($this->post->getKey()));
    $post->title = 'Would have been committed together with the file';
    new PostEntity($post)->attachFile(fileValueOf($this->upload));

    // The other request got there first.
    TemporaryUpload::query()->whereKey($this->upload->getKey())->update(['used_at' => now()]);

    expect(fn() => $this->manager->flush())->toThrow(TemporaryUploadAlreadyUsedException::class)
        ->and($this->post->fresh()->title)->not->toBe('Would have been committed together with the file')
        ->and($post->fresh()->mediaFiles()->count())->toBe(0)
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeTrue();
});

test('outside the manager the claim happens immediately, as the write does', function () {
    $this->post->addMedia(fileValueOf($this->upload))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    expect($this->upload->fresh()->used_at)->not->toBeNull()
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeFalse();
});

test('a file on a disk that is not a temporary upload is left alone', function () {
    Storage::disk(config('uploads.disk'))->put('elsewhere/report.pdf', 'contents');

    $media = $this->post->addMediaFromDisk('elsewhere/report.pdf', config('uploads.disk'))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    expect($media->exists)->toBeTrue()
        ->and(TemporaryUpload::query()->whereNotNull('used_at')->count())->toBe(0);
});
