<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Core\Upload\Models\ChunkedUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChunkedUploadFactory extends Factory
{
    protected $model = ChunkedUpload::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();
        $originalName = $this->faker->word() . '.bin';

        return [
            'uuid' => $uuid,
            'user_id' => User::factory(),
            'disk' => config('uploads.disk'),
            'path' => config('uploads.directory') . '/' . $uuid . '/' . $originalName,
            'original_name' => $originalName,
            'mime_type' => 'application/octet-stream',
            'total_size' => 1024,
            'received_bytes' => 0,
            'provider_state' => null,
        ];
    }
}
