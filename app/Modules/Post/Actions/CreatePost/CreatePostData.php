<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\CreatePost;

use App\Modules\Post\Domain\VO\PostAuthor;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Modules\Post\VO\PostCover;
use Spatie\LaravelData\Data;

class CreatePostData extends Data
{
    public function __construct(
        public PostTitle    $title,

        public ?PostContent $content,

        public ?PostCover   $cover,

        public PostAuthor   $authorUser,
    )
    {
    }
}
