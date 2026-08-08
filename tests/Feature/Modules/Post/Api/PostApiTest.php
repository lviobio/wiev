<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;

it('can list all posts', function () {
    $this->actingAsNewUser();
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
    $this->actingAsNewUser();
    $model = Post::factory()->create();

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
    $this->actingAsNewUser();
    $model = Post::factory()->create();

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
    $this->actingAsNewUser();
    $model = Post::factory()->create();

    $response = $this->deleteJson(route('api.v1.posts.destroy', [
        'post' => $model,
    ]));

    $response->assertNoContent();
});

it('can restore a soft-deleted post', function () {
    $this->actingAsNewUser();
    $model = Post::factory()->create();
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
    $this->actingAsNewUser();
    $model = Post::factory()->create();
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