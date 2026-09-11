<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Core\Upload\Models\TemporaryUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemporaryUploadFactory extends Factory
{
    protected $model = TemporaryUpload::class;

    public function definition(): array
    {
        $disk = config('uploads.disk');
        $uuid = (string) Str::uuid();
        $originalName = $this->faker->word() . '.txt';
        $path = config('uploads.directory') . '/' . $uuid . '/' . $originalName;

        // Stages a real file so a Cast/Rule reading the disk during a test finds
        // something there, same as a genuinely uploaded-then-staged file would.
        Storage::disk($disk)->put($path, $this->faker->sentence());

        return [
            'uuid' => $uuid,
            'user_id' => User::factory(),
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => 'text/plain',
            'size' => Storage::disk($disk)->size($path),
            'used_at' => null,
        ];
    }

    public function used(): static
    {
        return $this->state(fn(array $attributes): array => [
            'used_at' => now(),
        ]);
    }

    public function image(): static
    {
        return $this->state(function (array $attributes): array {
            $disk = $attributes['disk'] ?? config('uploads.disk');
            $uuid = $attributes['uuid'] ?? (string) Str::uuid();
            $originalName = 'cover.png';
            $path = config('uploads.directory') . '/' . $uuid . '/' . $originalName;

            // A minimal valid 1x1 PNG, so getimagesize()/fileinfo-based checks pass.
            Storage::disk($disk)->put($path, base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            ));

            return [
                'disk' => $disk,
                'uuid' => $uuid,
                'path' => $path,
                'original_name' => $originalName,
                'mime_type' => 'image/png',
                'size' => Storage::disk($disk)->size($path),
            ];
        });
    }
}
