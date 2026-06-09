<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\FromAuthenticatedUser;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdatePostData extends Data
{
    public function __construct(
        public string                     $title,
        public ?string                    $content,
        public UploadedFile|null|Optional $cover,
        #[FromAuthenticatedUser]
        public User                       $actorUser,
    )
    {
    }
}
