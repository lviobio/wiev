<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdatePostData extends Data
{
    public function __construct(
        public string                     $title,
        public string|null                $content,
        public UploadedFile|null|Optional $cover,
        public User                       $actorUser,
        public PostIdentifier             $id,
    )
    {
    }
}
