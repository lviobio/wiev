<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Models\TemporaryUpload;
use App\Core\Upload\Rules\TemporaryUploadRule;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
});

/** @return list<string> validation messages, empty when the value passes */
function uploadRuleErrors(mixed $value, ?TemporaryUploadRule $rule = null): array
{
    $validator = validator(['file' => $value], ['file' => [$rule ?? TemporaryUploadRule::make()]]);

    return $validator->errors()->get('file');
}

test('a value that is not a uuid fails with a single message', function () {
    $this->actingAs(User::factory()->create());

    expect(uploadRuleErrors('not-a-uuid'))->toBe(['The file must be a valid uploaded file reference.']);
});

test('an unknown identifier fails', function () {
    $this->actingAs(User::factory()->create());

    expect(uploadRuleErrors((string) Str::uuid()))->toBe(['The file could not be found, has expired or has already been used.']);
});

test('an upload owned by the current user passes', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->create(['user_id' => $user->getKey()]);

    expect(uploadRuleErrors($upload->uuid))->toBe([]);
});

test('an upload owned by someone else fails', function () {
    $this->actingAs(User::factory()->create());
    $upload = TemporaryUpload::factory()->create(['user_id' => User::factory()->create()->getKey()]);

    expect(uploadRuleErrors($upload->uuid))->not->toBe([]);
});

test('an already-used upload fails', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->used()->create(['user_id' => $user->getKey()]);

    expect(uploadRuleErrors($upload->uuid))->not->toBe([]);
});

test('image() rejects a non-image upload', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->create(['user_id' => $user->getKey(), 'mime_type' => 'application/pdf']);

    expect(uploadRuleErrors($upload->uuid, TemporaryUploadRule::make()->image()))
        ->toBe(['The file must be an image.']);
});

test('image() accepts an image upload', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->image()->create(['user_id' => $user->getKey()]);

    expect(uploadRuleErrors($upload->uuid, TemporaryUploadRule::make()->image()))->toBe([]);
});

test('mimeTypes() restricts to the listed types', function () {
    $this->actingAs($user = User::factory()->create());
    $pdf = TemporaryUpload::factory()->create(['user_id' => $user->getKey(), 'mime_type' => 'application/pdf']);
    $png = TemporaryUpload::factory()->image()->create(['user_id' => $user->getKey()]);

    $rule = TemporaryUploadRule::make()->mimeTypes('application/pdf');

    expect(uploadRuleErrors($pdf->uuid, $rule))->toBe([])
        ->and(uploadRuleErrors($png->uuid, $rule))->toBe(['The file must be a file of type: application/pdf.']);
});

test('maxSize() rejects an upload recorded larger than the limit', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->create(['user_id' => $user->getKey(), 'size' => 2048]);

    expect(uploadRuleErrors($upload->uuid, TemporaryUploadRule::make()->maxSize(1024)))
        ->toBe(['The file must not be larger than 1024 bytes.'])
        ->and(uploadRuleErrors($upload->uuid, TemporaryUploadRule::make()->maxSize(4096)))->toBe([]);
});

test('the rule only checks, it never claims', function () {
    $this->actingAs($user = User::factory()->create());
    $upload = TemporaryUpload::factory()->create(['user_id' => $user->getKey()]);

    uploadRuleErrors($upload->uuid);

    expect($upload->fresh()->used_at)->toBeNull();
});
