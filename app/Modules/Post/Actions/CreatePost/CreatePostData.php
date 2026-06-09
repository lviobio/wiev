<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\FromAuthenticatedUser;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class CreatePostData extends Data
{
    public function __construct(
        #[Min(3), Max(255)]
        public string        $title,

        public ?string       $content,

        public ?UploadedFile $cover,

        #[FromAuthenticatedUser]
        public User          $authorUser,
    )
    {
    }
}
