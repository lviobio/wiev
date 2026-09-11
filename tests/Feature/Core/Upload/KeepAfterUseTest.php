<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\ModelManager\ModelManagerContract;
use App\Core\Upload\Models\TemporaryUpload;
use App\Core\Upload\Rules\TemporaryUploadRule;
use App\Enums\AuthAbilityEnum;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileAction;
use App\Modules\Post\Actions\Files\AttachFile\AttachFileData;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Support\Facades\Storage;

/**
 * `uploads.keep_after_use_minutes` > 0: the media write copies the staged file instead
 * of moving it, and the owner may reference the same identifier again until the
 * window closes. See App\Core\Upload\TemporaryUploadClaimer.
 */
beforeEach(function () {
    config(['uploads.keep_after_use_minutes' => 30]);
    Storage::fake('public');
    Storage::fake(config('uploads.disk'));

    $this->post = Post::factory()->create();
    $this->actingAs($this->post->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);
    $this->upload = TemporaryUpload::factory()->create(['user_id' => $this->post->authorUser->getKey()]);
});

function attachUpload(Post $post, TemporaryUpload $upload): void
{
    resolve(AttachFileAction::class)(AttachFileData::from([
        'id' => $post->getKey(),
        'actorUser' => $post->authorUser,
        'file' => $upload->uuid,
    ]));
    resolve(ModelManagerContract::class)->clear();
}

test('the staged original survives the media write', function () {
    attachUpload($this->post, $this->upload);

    expect($this->post->fresh()->mediaFiles()->count())->toBe(1)
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeTrue()
        ->and($this->upload->fresh()->used_at)->not->toBeNull();
});

test('the same identifier can be used again inside the window', function () {
    attachUpload($this->post, $this->upload);
    $firstUse = $this->upload->fresh()->used_at;

    $this->travel(10)->minutes();
    attachUpload($this->post, $this->upload);

    expect($this->post->fresh()->mediaFiles()->count())->toBe(2)
        // The window is counted from the first use, not extended by later ones.
        ->and($this->upload->fresh()->used_at?->equalTo($firstUse))->toBeTrue();
});

test('a repeated request with the same identifier succeeds over HTTP', function () {
    foreach ([1, 2] as $attempt) {
        $this->postJson(route('api.v1.posts.files.store', ['post' => $this->post]), [
            'file' => $this->upload->uuid,
        ])->assertCreated();
    }

    expect($this->post->fresh()->mediaFiles()->count())->toBe(2);
});

test('the identifier expires once the window has passed', function () {
    attachUpload($this->post, $this->upload);

    $this->travel(31)->minutes();

    $errors = validator(['file' => $this->upload->uuid], ['file' => [TemporaryUploadRule::make()]])
        ->errors()->get('file');

    expect($errors)->toBe(['The file could not be found, has expired or has already been used.']);
    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->post]), [
        'file' => $this->upload->uuid,
    ])->assertStatus(422);
});

test('prune keeps a used upload until the window has passed, then removes it', function () {
    attachUpload($this->post, $this->upload);

    $this->travel(10)->minutes();
    $this->artisan('uploads:prune');
    expect(TemporaryUpload::query()->find($this->upload->getKey()))->not->toBeNull()
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeTrue();

    $this->travel(21)->minutes();
    $this->artisan('uploads:prune');
    expect(TemporaryUpload::query()->find($this->upload->getKey()))->toBeNull()
        ->and(Storage::disk($this->upload->disk)->exists($this->upload->path))->toBeFalse();
});

test('a file that is not a temporary upload is still moved, not copied', function () {
    Storage::disk(config('uploads.disk'))->put('elsewhere/report.pdf', 'contents');

    $this->post->addMediaFromDisk('elsewhere/report.pdf', config('uploads.disk'))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);

    expect(Storage::disk(config('uploads.disk'))->exists('elsewhere/report.pdf'))->toBeFalse();
});
