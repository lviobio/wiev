<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload\Api;

use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
});

function startChunkedUpload(array $overrides = []): array
{
    $response = test()->postJson(route('api.v1.uploads.chunked.store'), [
        'originalName' => 'big-file.bin',
        'mimeType' => 'application/octet-stream',
        'totalSize' => 11,
        ...$overrides,
    ])->assertCreated();

    return $response->json('data');
}

it('starts a session and returns its identifier with zero progress', function () {
    test()->actingAs(User::factory()->create());

    $data = startChunkedUpload();

    expect($data['uuid'])->toBeString()
        ->and($data['received_bytes'])->toBe(0)
        ->and($data['total_size'])->toBe(11)
        ->and(ChunkedUpload::query()->count())->toBe(1);
});

it('requires authentication to start a session', function () {
    test()->postJson(route('api.v1.uploads.chunked.store'), [
        'originalName' => 'a.bin',
        'mimeType' => null,
        'totalSize' => 1,
    ])->assertUnauthorized();
});

it('completes a session assembled from a single chunk into a claimable TemporaryUpload', function () {
    test()->actingAs($user = User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];

    test()->post(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('a.bin', 'hello world'),
        'offset' => 0,
    ])->assertOk();

    $response = test()->postJson(route('api.v1.uploads.chunked.complete', ['chunkedUpload' => $uuid]))
        ->assertCreated();

    expect($response->json('data.uuid'))->toBe($uuid)
        ->and($response->json('data.original_name'))->toBe('big-file.bin')
        ->and(TemporaryUpload::query()->where('uuid', $uuid)->exists())->toBeTrue()
        ->and(ChunkedUpload::query()->count())->toBe(0);
});

it('assembles a session from multiple sequential chunks', function () {
    test()->actingAs(User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];

    test()->post(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('a.bin', 'hello '),
        'offset' => 0,
    ])->assertOk();

    $response = test()->post(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('b.bin', 'world'),
        'offset' => 6,
    ])->assertOk();

    expect($response->json('data.received_bytes'))->toBe(11);

    $temporaryUpload = test()->postJson(route('api.v1.uploads.chunked.complete', ['chunkedUpload' => $uuid]))
        ->assertCreated()
        ->json('data');

    expect(Storage::disk(config('uploads.disk'))->get(
        TemporaryUpload::query()->where('uuid', $temporaryUpload['uuid'])->sole()->path,
    ))->toBe('hello world');
});

it('rejects a chunk at the wrong offset with the real offset to resync from', function () {
    test()->actingAs(User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];

    $response = test()->postJson(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('a.bin', 'hello world'),
        'offset' => 5,
    ])->assertStatus(409);

    expect($response->json('received_bytes'))->toBe(0);
});

it('refuses to complete a session before all bytes have arrived', function () {
    test()->actingAs(User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];

    test()->postJson(route('api.v1.uploads.chunked.complete', ['chunkedUpload' => $uuid]))
        ->assertStatus(422);
});

it('reports current progress for polling', function () {
    test()->actingAs(User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];

    test()->post(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('a.bin', 'hello '),
        'offset' => 0,
    ])->assertOk();

    $response = test()->getJson(route('api.v1.uploads.chunked.show', ['chunkedUpload' => $uuid]))
        ->assertOk();

    expect($response->json('data.received_bytes'))->toBe(6);
});

it('aborts a session, deleting the row and its staged bytes', function () {
    test()->actingAs(User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];
    $session = ChunkedUpload::query()->where('uuid', $uuid)->sole();

    test()->post(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('a.bin', 'partial'),
        'offset' => 0,
    ])->assertOk();

    test()->deleteJson(route('api.v1.uploads.chunked.destroy', ['chunkedUpload' => $uuid]))
        ->assertNoContent();

    expect(ChunkedUpload::query()->where('uuid', $uuid)->exists())->toBeFalse()
        ->and(Storage::disk($session->disk)->exists(dirname($session->path)))->toBeFalse();
});

it('treats another user\'s session as not found for every operation', function () {
    test()->actingAs(User::factory()->create());
    $uuid = startChunkedUpload()['uuid'];

    test()->actingAs(User::factory()->create());

    test()->getJson(route('api.v1.uploads.chunked.show', ['chunkedUpload' => $uuid]))->assertStatus(424);
    test()->postJson(route('api.v1.uploads.chunked.complete', ['chunkedUpload' => $uuid]))->assertStatus(424);
    test()->deleteJson(route('api.v1.uploads.chunked.destroy', ['chunkedUpload' => $uuid]))->assertStatus(424);
    // Plain post() (needed for a real file upload) sends no Accept header, unlike a
    // real client - explicit here so the app's shared "model not found" renderable
    // (which only formats a JSON body when the request wants JSON) actually applies.
    test()->post(route('api.v1.uploads.chunked.chunks.store', ['chunkedUpload' => $uuid]), [
        'chunk' => UploadedFile::fake()->createWithContent('a.bin', 'x'),
        'offset' => 0,
    ], ['Accept' => 'application/json'])->assertStatus(424);
});

it('returns 424 for an unknown session uuid', function () {
    test()->actingAs(User::factory()->create());

    test()->getJson(route('api.v1.uploads.chunked.show', ['chunkedUpload' => (string) \Illuminate\Support\Str::uuid()]))
        ->assertStatus(424);
});
