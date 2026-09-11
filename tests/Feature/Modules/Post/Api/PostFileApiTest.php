<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Api;

use App\Core\ModelManager\ModelManagerContract;
use App\Core\Upload\Models\TemporaryUpload;
use App\Enums\AuthAbilityEnum;
use App\Models\Media;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(config('uploads.disk'));

    $this->model = Post::factory()->create();
    $this->actingAs($this->model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);
});

function attachFileTo(Post $post, string $name = 'report.pdf', string $contents = 'the contents'): Media
{
    return $post->addMedia(UploadedFile::fake()->createWithContent($name, $contents))
        ->toMediaCollection(PostMediaCollectionEnum::Files->value);
}

it('lists the files of a post', function () {
    $file = attachFileTo($this->model);

    $response = $this->getJson(route('api.v1.posts.files.index', ['post' => $this->model]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.uuid'))->toBe($file->uuid)
        ->and($response->json('data.0.file_name'))->toBe('report.pdf')
        ->and($response->json('data.0.url'))->toBeString();
});

it('attaches a file to a post', function () {
    $upload = TemporaryUpload::factory()->create([
        'user_id' => $this->model->authorUser->getKey(),
        'original_name' => 'contract.pdf',
    ]);

    $response = $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => $upload->uuid,
    ])->assertCreated();

    expect($response->json('data.file_name'))->toBe('contract.pdf')
        ->and($this->model->fresh()->mediaFiles()->count())->toBe(1);
});

it('rejects an attachment that is not a valid upload reference', function () {
    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => 'not a file',
    ])->assertStatus(422);
});

it('rejects an attachment referencing an upload it does not own', function () {
    $upload = TemporaryUpload::factory()->create();

    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => $upload->uuid,
    ])->assertStatus(422);
});

it('rejects an attachment referencing an already-used upload', function () {
    $upload = TemporaryUpload::factory()->used()->create([
        'user_id' => $this->model->authorUser->getKey(),
    ]);

    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => $upload->uuid,
    ])->assertStatus(422);
});

it('renames a file', function () {
    $file = attachFileTo($this->model);

    $this->patchJson(route('api.v1.posts.files.update', ['post' => $this->model, 'file' => $file->uuid]), [
        'name' => 'Annual report',
    ])->assertOk();

    expect($file->fresh()->name)->toBe('Annual report');
});

it('detaches a file', function () {
    $file = attachFileTo($this->model);

    $this->deleteJson(route('api.v1.posts.files.destroy', ['post' => $this->model, 'file' => $file->uuid]))
        ->assertNoContent();

    expect($this->model->fresh()->mediaFiles()->count())->toBe(0);
});

it('downloads a file', function () {
    $file = attachFileTo($this->model);

    $response = $this->get(route('api.v1.posts.files.download', ['post' => $this->model, 'file' => $file->uuid]));

    $response->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="report.pdf"');

    expect($response->streamedContent())->toBe('the contents');
});

it('reports a missing file', function () {
    $this->deleteJson(route('api.v1.posts.files.destroy', [
        'post' => $this->model,
        'file' => Str::uuid()->toString(),
    ]))->assertStatus(424);
});

it('forbids a stranger from touching the files', function () {
    $file = attachFileTo($this->model);

    $this->actingAsNewUser()->allowActingUser(AuthAbilityEnum::Access, Post::class);
    $upload = TemporaryUpload::factory()->create(['user_id' => auth()->id()]);

    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => $upload->uuid,
    ])->assertForbidden();

    $this->patchJson(route('api.v1.posts.files.update', ['post' => $this->model, 'file' => $file->uuid]), [
        'name' => 'Hijacked',
    ])->assertForbidden();

    $this->deleteJson(route('api.v1.posts.files.destroy', ['post' => $this->model, 'file' => $file->uuid]))
        ->assertForbidden();

    // читать файлы поста может любой аутентифицированный, как и сам пост
    $this->getJson(route('api.v1.posts.files.index', ['post' => $this->model]))->assertOk();

    expect($this->model->fresh()->mediaFiles()->count())->toBe(1);
});

it('can retry with the same file after a domain-level authorization check fails', function () {
    // The controller-level ability check only requires generic Access - it's
    // AttachFileAction's own in-action Gate::authorize('update', $post) that rejects
    // this, well after the Data object (and the file it claimed) already exists.
    $this->actingAsNewUser()->allowActingUser(AuthAbilityEnum::Access, Post::class);
    $upload = TemporaryUpload::factory()->create(['user_id' => auth()->id()]);

    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => $upload->uuid,
    ])->assertForbidden();


    // The file must not have been burned by the failed attempt.
    $ownPost = Post::factory()->create(['author_user_id' => auth()->id()]);

    $this->postJson(route('api.v1.posts.files.store', ['post' => $ownPost]), [
        'file' => $upload->uuid,
    ])->assertCreated();

    expect($ownPost->fresh()->mediaFiles()->count())->toBe(1);
});

it('answers 409 when another request used the same file first', function () {
    $upload = TemporaryUpload::factory()->create(['user_id' => $this->model->authorUser->getKey()]);

    // Registered before the request, so it runs first inside the flush transaction:
    // stands in for a concurrent request that claimed the file a moment earlier.
    resolve(ModelManagerContract::class)->beforeCommit(
        fn() => TemporaryUpload::query()->whereKey($upload->getKey())->update(['used_at' => now()]),
    );

    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => $upload->uuid,
    ])->assertStatus(409);

    expect($this->model->fresh()->mediaFiles()->count())->toBe(0)
        ->and(Storage::disk($upload->disk)->exists($upload->path))->toBeTrue();
});

it('requires the access ability', function () {
    $this->actingAsNewUser();

    $this->getJson(route('api.v1.posts.files.index', ['post' => $this->model]))->assertForbidden();
});
