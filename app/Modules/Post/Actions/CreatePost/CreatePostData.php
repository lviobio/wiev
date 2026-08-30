<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Modules\Post\Domain\VO\PostAuthor;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Modules\Post\VO\PostCover;
use OpenApi\Attributes as OA;
use Spatie\LaravelData\Data;

#[OA\Schema(required: ['title'])]
class CreatePostData extends Data
{
    public function __construct(
        #[OA\Property(type: 'string')]
        public PostTitle    $title,

        #[OA\Property(type: 'string', nullable: true)]
        public ?PostContent $content,

        #[OA\Property(type: 'string', format: 'binary', nullable: true)]
        public ?PostCover   $cover,

        public PostAuthor   $authorUser,
    )
    {
    }
}
