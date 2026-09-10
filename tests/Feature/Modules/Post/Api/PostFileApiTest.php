<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post\Api;

use App\Enums\AuthAbilityEnum;
use App\Models\Media;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('public');

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
    $response = $this->post(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => UploadedFile::fake()->create('contract.pdf', 4),
    ])->assertCreated();

    expect($response->json('data.file_name'))->toBe('contract.pdf')
        ->and($this->model->fresh()->mediaFiles()->count())->toBe(1);
});

it('rejects an attachment that is not a file', function () {
    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => 'not a file',
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

    $this->postJson(route('api.v1.posts.files.store', ['post' => $this->model]), [
        'file' => UploadedFile::fake()->create('hijack.pdf', 1),
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

it('requires the access ability', function () {
    $this->actingAsNewUser();

    $this->getJson(route('api.v1.posts.files.index', ['post' => $this->model]))->assertForbidden();
});
