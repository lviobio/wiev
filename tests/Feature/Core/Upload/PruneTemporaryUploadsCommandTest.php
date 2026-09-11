<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
});

test('it deletes the row of a used upload whose file is already gone', function () {
    $upload = TemporaryUpload::factory()->used()->create();
    Storage::disk($upload->disk)->delete($upload->path);

    $this->artisan('uploads:prune')->assertExitCode(0);

    expect(TemporaryUpload::query()->find($upload->getKey()))->toBeNull()
        ->and(Storage::disk($upload->disk)->exists(dirname($upload->path)))->toBeFalse();
});

test('it gives a used upload whose file is still there a grace period', function () {
    $fresh = TemporaryUpload::factory()->create(['used_at' => now()->subMinutes(5)]);
    $stale = TemporaryUpload::factory()->create([
        'used_at' => now()->subMinutes((int) config('uploads.used_grace_minutes') + 1),
    ]);

    $this->artisan('uploads:prune');

    expect(TemporaryUpload::query()->find($fresh->getKey()))->not->toBeNull()
        ->and(Storage::disk($fresh->disk)->exists($fresh->path))->toBeTrue()
        ->and(TemporaryUpload::query()->find($stale->getKey()))->toBeNull()
        ->and(Storage::disk($stale->disk)->exists($stale->path))->toBeFalse();
});

test('it deletes unclaimed uploads past the ttl', function () {
    $upload = TemporaryUpload::factory()->create([
        'created_at' => now()->subHours((int) config('uploads.ttl_hours') + 1),
    ]);

    $this->artisan('uploads:prune');

    expect(TemporaryUpload::query()->find($upload->getKey()))->toBeNull()
        ->and(Storage::disk($upload->disk)->exists($upload->path))->toBeFalse();
});

test('it leaves unclaimed uploads inside the ttl alone', function () {
    $upload = TemporaryUpload::factory()->create([
        'created_at' => now()->subHours(1),
    ]);

    $this->artisan('uploads:prune');

    expect(TemporaryUpload::query()->find($upload->getKey()))->not->toBeNull()
        ->and(Storage::disk($upload->disk)->exists($upload->path))->toBeTrue();
});
