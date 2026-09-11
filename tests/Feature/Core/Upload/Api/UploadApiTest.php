<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload\Api;

use App\Core\Upload\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
});

it('stages a file and returns its identifier', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('api.v1.uploads.store'), [
        'file' => UploadedFile::fake()->create('contract.pdf', 4),
    ])->assertCreated();

    expect($response->json('data.uuid'))->toBeString()
        ->and($response->json('data.original_name'))->toBe('contract.pdf')
        ->and(TemporaryUpload::query()->count())->toBe(1);
});

it('records the owner and the staged file on disk', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('api.v1.uploads.store'), [
        'file' => UploadedFile::fake()->create('contract.pdf', 4),
    ])->assertCreated();

    $upload = TemporaryUpload::query()->sole();

    expect($upload->user_id)->toBe($user->getKey())
        ->and($upload->used_at)->toBeNull()
        ->and(Storage::disk($upload->disk)->exists($upload->path))->toBeTrue();
});

it('rejects an oversized upload', function () {
    $this->actingAs(User::factory()->create());

    $tooLarge = intdiv((int) config('media-library.max_file_size'), 1024) + 1;

    $this->postJson(route('api.v1.uploads.store'), [
        'file' => UploadedFile::fake()->create('huge.bin', $tooLarge),
    ])->assertStatus(422);
});

it('requires authentication', function () {
    $this->postJson(route('api.v1.uploads.store'), [
        'file' => UploadedFile::fake()->create('contract.pdf', 4),
    ])->assertUnauthorized();
});
