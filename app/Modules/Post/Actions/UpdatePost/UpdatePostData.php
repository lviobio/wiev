<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Illuminate\Http\UploadedFile;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

#[OA\Schema(required: ['title'])]
class UpdatePostData extends Data
{
    public function __construct(
        #[OA\Property]
        public string                     $title,
        #[OA\Property]
        public string|null                $content,
        #[OA\Property(type: 'string', format: 'binary', nullable: true)]
        public UploadedFile|null|Optional $cover,
        public User                       $actorUser,
        public PostIdentifier             $id,
    )
    {
    }
}
