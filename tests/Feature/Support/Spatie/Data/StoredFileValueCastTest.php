<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Spatie\Data;

use App\Core\Upload\Models\TemporaryUpload;
use App\Core\Upload\Rules\TemporaryUploadRule;
use App\Models\User;
use App\Modules\Post\Actions\CreatePost\CreatePostAction;
use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Actions\UpdatePost\UpdatePostData;
use App\Modules\Post\Domain\VO\PostCover;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use TypeError;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(config('uploads.disk'));
});

function coverDataFor(User $user, mixed $cover): CreatePostData
{
    return CreatePostData::from([
        'title' => 'Test title',
        'content' => null,
        'cover' => $cover,
        'authorUser' => $user,
    ]);
}

test('a temporary upload identifier becomes a stored file value - without claiming it', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->image()->create(['user_id' => $user->getKey()]);

    $data = coverDataFor($user, $upload->uuid);

    expect($data->cover)->toBeInstanceOf(PostCover::class)
        ->and($data->cover->disk)->toBe($upload->disk)
        ->and($data->cover->path)->toBe($upload->path)
        ->and($data->cover->originalName)->toBe($upload->original_name)
        ->and($data->cover->mimeType)->toBe($upload->mime_type)
        ->and($data->cover->size)->toBe($upload->size)
        // Claiming is the flush's job, at commit - see ClaimAtFlushTest.
        ->and($upload->fresh()->used_at)->toBeNull();
});

test('an absent cover stays absent', function () {
    $withNull = coverDataFor(User::factory()->create(), null);

    $withoutKey = UpdatePostData::from([
        'title' => 'Test title',
        'content' => null,
        'actorUser' => User::factory()->create(),
        'id' => 1,
    ]);

    expect($withNull->cover)->toBeNull()
        ->and($withoutKey->cover)->toBeInstanceOf(Optional::class);
});

test('the original file name survives the trip to media library', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->image()->create([
        'user_id' => $user->getKey(),
        'original_name' => 'my photo.png',
    ]);

    $model = resolve(CreatePostAction::class)(coverDataFor($user, $upload->uuid));

    $media = $model->fresh()->getFirstMedia(PostMediaCollectionEnum::Cover->value);

    // без переходника media library вывела бы имя из пути на диске (uuid-каталог)
    expect($media->file_name)->toBe('my-photo.png')
        ->and($media->name)->toBe('my photo')
        ->and($upload->fresh()->used_at)->not->toBeNull();
});

test('an already-used upload is rejected as a validation error', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->image()->used()->create(['user_id' => $user->getKey()]);

    expect(fn() => coverDataFor($user, $upload->uuid))->toThrow(ValidationException::class);
});

test('an upload owned by someone else is rejected as a validation error', function () {
    $this->actingAs($actor = User::factory()->create());
    $upload = TemporaryUpload::factory()->image()->create(['user_id' => User::factory()->create()->getKey()]);

    expect(fn() => coverDataFor($actor, $upload->uuid))->toThrow(ValidationException::class)
        ->and($upload->fresh()->used_at)->toBeNull();
});

test('a value that is not an identifier cannot be cast at all', function () {
    $this->actingAs($user = User::factory()->create());

    expect(fn() => coverDataFor($user, 'not a file'))->toThrow(TypeError::class);
});

test('the cover is validated with the temporary upload rule', function () {
    $rules = CreatePostData::getValidationRules([]);

    expect(collect($rules['cover'])->contains(fn(mixed $rule): bool => $rule instanceof TemporaryUploadRule))
        ->toBeTrue();
});
