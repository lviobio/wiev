<?php
declare(strict_types=1);

namespace App\Modules\Post\Data;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class PostCreateData extends Data
{
    public function __construct(
        public string        $title,
        public ?string       $content,
        public ?UploadedFile $cover,
        public User          $author,
    )
    {
    }
}
