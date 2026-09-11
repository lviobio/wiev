<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Actions\StoreUpload\StoreUploadAction;
use App\Core\Upload\Actions\StoreUpload\StoreUploadData;
use App\Core\Upload\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
});

test('it stores the file on the configured disk and records matching metadata', function () {
    $user = User::factory()->create();

    $upload = resolve(StoreUploadAction::class)(StoreUploadData::from([
        'file' => UploadedFile::fake()->create('contract draft.pdf', 4),
        'actorUser' => $user,
    ]));

    expect($upload)->toBeInstanceOf(TemporaryUpload::class)
        ->and($upload->exists)->toBeTrue()
        ->and($upload->uuid)->not->toBeNull()
        ->and($upload->user_id)->toBe($user->getKey())
        ->and($upload->disk)->toBe(config('uploads.disk'))
        ->and($upload->original_name)->toBe('contract draft.pdf')
        ->and($upload->used_at)->toBeNull()
        ->and(Storage::disk($upload->disk)->exists($upload->path))->toBeTrue();
});

test('two uploads from the same user get distinct identifiers and paths', function () {
    $user = User::factory()->create();

    $first = resolve(StoreUploadAction::class)(StoreUploadData::from([
        'file' => UploadedFile::fake()->create('a.pdf', 1),
        'actorUser' => $user,
    ]));

    $second = resolve(StoreUploadAction::class)(StoreUploadData::from([
        'file' => UploadedFile::fake()->create('b.pdf', 1),
        'actorUser' => $user,
    ]));

    expect($first->uuid)->not->toBe($second->uuid)
        ->and($first->path)->not->toBe($second->path);
});

test('two uploads with the same file name never share a location', function () {
    $user = User::factory()->create();

    $first = resolve(StoreUploadAction::class)(StoreUploadData::from([
        'file' => UploadedFile::fake()->create('cover.jpg', 1),
        'actorUser' => $user,
    ]));

    $second = resolve(StoreUploadAction::class)(StoreUploadData::from([
        'file' => UploadedFile::fake()->create('cover.jpg', 1),
        'actorUser' => $user,
    ]));

    // The location is how the file is identified when it becomes media, so it has to
    // name exactly one upload - the per-upload uuid directory is what guarantees that.
    expect($first->path)->not->toBe($second->path)
        ->and($first->path)->toStartWith(config('uploads.directory') . '/' . $first->uuid . '/')
        ->and(fn() => TemporaryUpload::factory()->create(['disk' => $first->disk, 'path' => $first->path]))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});
