<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Enums\AuthAbilityEnum;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;

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
    $this->actingAsNewUser();
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
    $this->actingAsNewUser();
    $model = Post::factory()->make();

    $response = $this->postJson(route('api.v1.posts.store'), [
        'title' => $model->title,
        'content' => $model->content,
    ]);

    $response->assertCreated();
});

it('can update an existing post', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser);

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

it('can update an existing post with a cover through method spoofing', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser);

    $response = $this->post(route('api.v1.posts.update', [
        'post' => $model,
    ]), [
        '_method' => 'PUT',
        'title' => 'Updated title',
        'content' => 'Updated content',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ]);

    $response->assertOk();

    expect($response->json('data.title'))->toBe('Updated title')
        ->and($response->json('data.cover'))->toBeString();
});

it('can delete a post', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser);

    $response = $this->deleteJson(route('api.v1.posts.destroy', [
        'post' => $model,
    ]));

    $response->assertNoContent();
});

it('can restore a soft-deleted post', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser);
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
    $this->actingAsNewUser();

    $this->postJson(route('api.v1.posts.restore', ['post' => 999999]))
        ->assertStatus(424);
});

it('can remove a post cover', function () {
    $model = Post::factory()->create();
    $this->actingAs($model->authorUser);
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
    $this->actingAsNewUser();

    $this->deleteJson(route('api.v1.posts.cover.destroy', ['post' => 999999]))
        ->assertStatus(424);
});