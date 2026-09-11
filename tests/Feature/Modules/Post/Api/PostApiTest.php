<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Core\Upload\Models\TemporaryUpload;
use App\Enums\AuthAbilityEnum;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(config('uploads.disk'));
});

/**
 * Pairs with the test below: it is what makes the ability granted there load-bearing.
 * Drop `->ability()` from the declaration and this fails, rather than the grant quietly
 * becoming a no-op nobody notices.
 */
it('requires the access ability to list posts', function () {
    $this->actingAsNewUser();

    $this->getJson(route('api.v1.posts.index'))->assertForbidden();
});

it('can list all posts', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);
    $collection = Post::factory()->count(10)->create();

    $response = $this->getJson(route('api.v1.posts.index'));

    expect($response->json('data'))->toBe($collection->map(fn(Post $model) => [
        'id' => $model->getKey(),
        'title' => $model->title,
        'content' => $model->content,
        'deleted_at' => null,
        'created_at' => $this->castApiDate($model->created_at),
        'updated_at' => $this->castApiDate($model->updated_at),
    ])->all());
});

it('can retrieve a specific post', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);
    $model = Post::factory()->create();

    $response = $this->getJson(route('api.v1.posts.show', [
        'post' => $model,
    ]))->assertOk();

    $response->assertExactJson([
        'data' => [
            'id' => $model->getKey(),
            'title' => $model->title,
            'content' => $model->content,
            'deleted_at' => null,
            'created_at' => $this->castApiDate($model->created_at),
            'updated_at' => $this->castApiDate($model->updated_at),
        ]
    ]);
});

it('can create a new post', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);
    $model = Post::factory()->make();

    $response = $this->postJson(route('api.v1.posts.store'), [
        'title' => $model->title,
        'content' => $model->content,
    ]);

    $response->assertCreated();
});

it('can create a new post with a cover staged through the uploads endpoint', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);
    $model = Post::factory()->make();

    $upload = $this->post(route('api.v1.uploads.store'), [
        'file' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertCreated()->json('data');

    $response = $this->postJson(route('api.v1.posts.store'), [
        'title' => $model->title,
        'content' => $model->content,
        'cover' => $upload['uuid'],
    ]);

    $response->assertCreated();

    $created = Post::query()->findOrFail($response->json('data.id'));

    expect($response->json('data.cover'))->toBeString()
        ->and($created->getFirstMedia(PostMediaCollectionEnum::Cover->value))->not->toBeNull();
});

it('can retry with the same cover after a sibling field fails validation', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);

    $upload = $this->post(route('api.v1.uploads.store'), [
        'file' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertCreated()->json('data');

    // 'ab' is shorter than PostTitle's own min:3 - the cover itself is perfectly
    // valid, but the request as a whole must still fail.
    $this->postJson(route('api.v1.posts.store'), [
        'title' => 'ab',
        'content' => null,
        'cover' => $upload['uuid'],
    ])->assertStatus(422);

    // The cover must not have been burned by the failed attempt.
    $response = $this->postJson(route('api.v1.posts.store'), [
        'title' => 'A valid title',
        'content' => null,
        'cover' => $upload['uuid'],
    ]);

    $response->assertCreated();

    $created = Post::query()->findOrFail($response->json('data.id'));

    expect($created->getFirstMedia(PostMediaCollectionEnum::Cover->value))->not->toBeNull();
});

it('can update an existing post', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);

    $response = $this->putJson(route('api.v1.posts.update', [
        'post' => $model,
    ]), [
        'title' => $model->title,
        'content' => $model->content,
    ]);

    $response->assertOk();

    $response->assertExactJson([
        'data' => [
            'id' => $model->getKey(),
            'title' => $model->title,
            'content' => $model->content,
            'deleted_at' => null,
            'created_at' => $this->castApiDate($model->created_at),
            'updated_at' => $this->castApiDate($model->updated_at),
        ]
    ]);
});

it('can update an existing post with a cover uploaded ahead of time', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);

    $upload = TemporaryUpload::factory()->image()->create(['user_id' => $model->authorUser->getKey()]);

    $response = $this->putJson(route('api.v1.posts.update', [
        'post' => $model,
    ]), [
        'title' => 'Updated title',
        'content' => 'Updated content',
        'cover' => $upload->uuid,
    ]);

    $response->assertOk();

    expect($response->json('data.title'))->toBe('Updated title')
        ->and($response->json('data.cover'))->toBeString();
});

it('rejects a cover referencing an upload it does not own', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);

    $upload = TemporaryUpload::factory()->image()->create();

    $this->putJson(route('api.v1.posts.update', ['post' => $model]), [
        'title' => 'Updated title',
        'content' => 'Updated content',
        'cover' => $upload->uuid,
    ])->assertStatus(422);
});

it('can delete a post', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);

    $response = $this->deleteJson(route('api.v1.posts.destroy', [
        'post' => $model,
    ]));

    $response->assertNoContent();
});

it('can restore a soft-deleted post', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);
    $model->delete();

    expect($model->fresh()->trashed())->toBeTrue();

    $response = $this->postJson(route('api.v1.posts.restore', [
        'post' => $model,
    ]));

    $response->assertOk();

    expect($model->fresh()->trashed())->toBeFalse()
        ->and($response->json('data.id'))->toBe($model->getKey())
        ->and($response->json('data.deleted_at'))->toBeNull();
});

it('cannot restore a missing post', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);

    $this->postJson(route('api.v1.posts.restore', ['post' => 999999]))
        ->assertStatus(424);
});

it('can remove a post cover', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser)->allowActingUser(AuthAbilityEnum::Access, Post::class);
    $model->addMedia(UploadedFile::fake()->image('cover.jpg'))
        ->toMediaCollection(PostMediaCollectionEnum::Cover->value);

    $response = $this->deleteJson(route('api.v1.posts.cover.destroy', [
        'post' => $model,
    ]));

    $response->assertNoContent();

    expect($model->fresh()->getMedia(PostMediaCollectionEnum::Cover->value))->toBeEmpty();

    // The post itself survives - only its cover is gone.
    $this->getJson(route('api.v1.posts.show', ['post' => $model]))
        ->assertOk()
        ->assertJsonMissingPath('data.cover');
});

it('cannot remove the cover of a missing post', function () {
    $this->actingAsUserAllowedTo(AuthAbilityEnum::Access, Post::class);

    $this->deleteJson(route('api.v1.posts.cover.destroy', ['post' => 999999]))
        ->assertStatus(424);
});