<?php
declare(strict_types=1);

namespace Tests\Feature\Core\Upload;

use App\Core\Upload\Chunked\LocalChunkedUploadStrategy;
use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

beforeEach(function () {
    Storage::fake(config('uploads.disk'));
    $this->strategy = resolve(LocalChunkedUploadStrategy::class);
});

function chunkedSession(array $overrides = []): ChunkedUpload
{
    return ChunkedUpload::factory()->create($overrides);
}

test('start creates the session directory', function () {
    $session = chunkedSession();

    $this->strategy->start($session);

    expect(Storage::disk($session->disk)->exists(dirname($session->path)))->toBeTrue();
});

test('appending chunks in order assembles them byte for byte', function () {
    $session = chunkedSession(['total_size' => 11]);
    $this->strategy->start($session);

    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', 'hello '), offset: 0);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('b.bin', 'world'), offset: 6);

    expect(Storage::disk($session->disk)->get($session->path))->toBe('hello world');
});

test('a chunk written at a given offset lands exactly there, regardless of arrival order', function () {
    $session = chunkedSession(['total_size' => 11]);
    $this->strategy->start($session);

    // The strategy trusts its caller's offset - the Action is what enforces order
    // (see AppendChunkedUploadChunkAction). Exercised directly here.
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('b.bin', 'world'), offset: 6);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', 'hello '), offset: 0);

    expect(Storage::disk($session->disk)->get($session->path))->toBe('hello world');
});

test('complete finalizes a TemporaryUpload in place - nothing is moved', function () {
    $session = chunkedSession(['total_size' => 11, 'mime_type' => 'application/x-client-declared']);
    $session->path = dirname($session->path) . '/hello.txt';
    $session->original_name = 'hello.txt';
    $session->save();
    $this->strategy->start($session);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', 'hello world'), offset: 0);

    $upload = $this->strategy->complete($session);

    expect($upload)->toBeInstanceOf(TemporaryUpload::class)
        ->and($upload->uuid)->toBe($session->uuid)
        ->and($upload->disk)->toBe($session->disk)
        ->and($upload->path)->toBe($session->path)
        ->and($upload->user_id)->toBe($session->user_id)
        ->and($upload->original_name)->toBe($session->original_name)
        ->and($upload->size)->toBe(11)
        // Sniffed from the real file, not copied from the (client-declared) session field.
        ->and($upload->mime_type)->toBe('text/plain');
});

test('complete refuses to finalize a file whose actual size disagrees with the declared total', function () {
    $session = chunkedSession(['total_size' => 999]);
    $this->strategy->start($session);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', 'too short'), offset: 0);

    expect(fn() => $this->strategy->complete($session))->toThrow(RuntimeException::class);
});

test('abort removes the whole session directory, not just the partial file', function () {
    $session = chunkedSession(['total_size' => 999]);
    $this->strategy->start($session);
    $this->strategy->appendChunk($session, UploadedFile::fake()->createWithContent('a.bin', 'partial'), offset: 0);

    $this->strategy->abort($session);

    expect(Storage::disk($session->disk)->exists(dirname($session->path)))->toBeFalse();
});
