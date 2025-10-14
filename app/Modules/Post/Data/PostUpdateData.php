<?php
declare(strict_types=1);

namespace App\Modules\Post\Data;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class PostUpdateData extends Data
{
    public function __construct(
        public string                     $title,
        public ?string                    $content,
        public UploadedFile|null|Optional $cover,
        public User                       $operatingUser,
    )
    {
    }
}
