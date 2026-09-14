<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Models\ChunkedUpload;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
});

test('it leaves a recent session alone', function () {
    $session = ChunkedUpload::factory()->create(['created_at' => now()]);
    Storage::disk($session->disk)->put($session->path, 'partial');

    $this->artisan('uploads:prune-chunked')->assertExitCode(0);

    expect(ChunkedUpload::query()->find($session->getKey()))->not->toBeNull()
        ->and(Storage::disk($session->disk)->exists($session->path))->toBeTrue();
});

test('it deletes an abandoned session past the ttl, staged bytes included', function () {
    $session = ChunkedUpload::factory()->create([
        'created_at' => now()->subHours((int) config('uploads.chunked.ttl_hours') + 1),
    ]);
    Storage::disk($session->disk)->put($session->path, 'partial');

    $this->artisan('uploads:prune-chunked');

    expect(ChunkedUpload::query()->find($session->getKey()))->toBeNull()
        ->and(Storage::disk($session->disk)->exists(dirname($session->path)))->toBeFalse();
});
