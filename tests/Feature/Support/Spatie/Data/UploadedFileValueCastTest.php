<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Spatie\Data;

use App\Core\Upload\Actions\StoreUpload\StoreUploadData;
use App\Core\Upload\VO\NewUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('an uploaded file becomes a domain value', function () {
    $data = StoreUploadData::from([
        'file' => UploadedFile::fake()->image('my photo.jpg'),
        'actorUser' => User::factory()->create(),
    ]);

    expect($data->file)->toBeInstanceOf(NewUpload::class)
        ->and($data->file->originalName)->toBe('my photo.jpg')
        ->and($data->file->mimeType)->toBe('image/jpeg')
        ->and($data->file->size)->toBeGreaterThan(0)
        ->and($data->file->source)->toBeInstanceOf(UploadedFile::class);
});

test('the raw upload is validated as a file within the media library limit', function () {
    $rules = StoreUploadData::getValidationRules([]);

    expect($rules['file'])->toContain('file')
        ->and($rules['file'])->toContain('max:102400');
});
