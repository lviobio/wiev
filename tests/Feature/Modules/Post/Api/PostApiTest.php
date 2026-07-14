<?php
declare(strict_types=1);

namespace Tests\Feature\Modules\Post;

use App\Modules\Post\Models\Post;

it('can retrieve a specific post', function () {
    $this->actingAsNewUser();
    $model = Post::factory()->create();

    $response = $this->getJson(route('api.v1.posts.show', [
        'post' => $model,
    ]))->assertStatus(200);

    $response->assertExactJson([
        'data' => [
            'id' => $model->getKey(),
            'title' => $model->title,
            'content' => $model->content,
            'deleted_at' => null,
            'created_at' => $model->created_at->getTimestampMs(),
            'updated_at' => $model->updated_at->getTimestampMs(),
        ]
    ]);
});

it('can list all posts', function () {
    $this->actingAsNewUser();
    $collection = Post::factory()->count(10)->create();

    $response = $this->getJson(route('api.v1.posts.index'));

    expect($response->json('data'))->toBe($collection->map(fn(Post $model) => [
        'id' => $model->getKey(),
        'title' => $model->title,
        'content' => $model->content,
        'deleted_at' => null,
        'created_at' => $model->created_at->getTimestampMs(),
        'updated_at' => $model->updated_at->getTimestampMs(),
    ])->all());
});