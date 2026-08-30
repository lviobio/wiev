<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Spatie\Data;

use App\Models\User;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Domain\VO\PostAuthor;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostTitle;
use DomainException;
use Illuminate\Support\Facades\Validator;

function payload(array $overrides = []): array
{
    return [
        'title' => 'Test title',
        'content' => 'Test content',
        'cover' => null,
        'authorUser' => User::factory()->create(),
        ...$overrides,
    ];
}

test('strings become domain values', function () {
    $data = CreatePostData::from(payload());

    expect($data->title)->toBeInstanceOf(PostTitle::class)
        ->and($data->title->value)->toBe('Test title')
        ->and($data->content)->toBeInstanceOf(PostContent::class)
        ->and($data->content->value)->toBe('Test content');
});

test('an absent content stays null', function () {
    expect(CreatePostData::from(payload(['content' => null]))->content)->toBeNull();
});

test('the authenticated user becomes the author', function () {
    $user = User::factory()->create();

    $data = CreatePostData::from(payload(['authorUser' => $user]));

    expect($data->authorUser)->toBeInstanceOf(PostAuthor::class)
        ->and($data->authorUser->identity->getModel())->toBe($user);
});

test('the value object still guards its own invariant', function () {
    CreatePostData::from(payload(['title' => 'ab']));
})->throws(DomainException::class);

test('validation rejects a bad title before the value object sees it', function () {
    $rules = CreatePostData::getValidationRules([]);

    expect($rules['title'])->toBe(['required', 'string', 'min:3', 'max:255'])
        ->and($rules['content'])->toBe(['nullable', 'string', 'max:65535']);

    $validator = Validator::make(['title' => 'ab'], ['title' => $rules['title']]);

    expect($validator->fails())->toBeTrue();
});
