<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

#[OA\Schema(required: ['title'])]
class CreatePostData extends Data
{
    public function __construct(
        #[OA\Property]
        #[Min(3), Max(255)]
        public string        $title,

        #[OA\Property]
        public ?string       $content,

        #[OA\Property(type: 'string', format: 'binary', nullable: true)]
        public ?UploadedFile $cover,

        public User          $authorUser,
    )
    {
    }
}
