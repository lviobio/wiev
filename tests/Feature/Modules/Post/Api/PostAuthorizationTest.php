<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\UploadedFile;

/**
 * Only the author may change a post. Reading stays open to any authenticated user.
 */
beforeEach(function () {
    $this->model = Post::factory()->create();
    $this->stranger = $this->actingAsNewUser();
});

it('lets a stranger read a post', function () {
    $this->getJson(route('api.v1.posts.show', ['post' => $this->model]))->assertOk();
    $this->getJson(route('api.v1.posts.index'))->assertOk();
});

it('forbids a stranger from updating a post', function () {
    $this->putJson(route('api.v1.posts.update', ['post' => $this->model]), [
        'title' => 'Hijacked',
    ])->assertForbidden();

    expect($this->model->fresh()->title)->not->toBe('Hijacked');
});

it('forbids a stranger from deleting a post', function () {
    $this->deleteJson(route('api.v1.posts.destroy', ['post' => $this->model]))
        ->assertForbidden();

    expect($this->model->fresh()->trashed())->toBeFalse();
});

it('forbids a stranger from restoring a post', function () {
    $this->model->delete();

    $this->postJson(route('api.v1.posts.restore', ['post' => $this->model]))
        ->assertForbidden();

    expect($this->model->fresh()->trashed())->toBeTrue();
});

it('forbids a stranger from removing a cover', function () {
    $this->model->addMedia(UploadedFile::fake()->image('cover.jpg'))
        ->toMediaCollection(PostMediaCollectionEnum::Cover->value);

    $this->deleteJson(route('api.v1.posts.cover.destroy', ['post' => $this->model]))
        ->assertForbidden();

    expect($this->model->fresh()->getMedia(PostMediaCollectionEnum::Cover->value))->toHaveCount(1);
});

it('reports a missing post before it reports a forbidden one', function () {
    // findOrFail runs first, so a stranger cannot probe which ids exist.
    $this->deleteJson(route('api.v1.posts.destroy', ['post' => 999999]))
        ->assertStatus(424);
});
