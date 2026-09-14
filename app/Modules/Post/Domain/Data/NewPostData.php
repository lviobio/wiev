<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\Data;

use App\Modules\Post\Domain\VO\PostAuthor;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostCover;
use App\Modules\Post\Domain\VO\PostTitle;

class NewPostData
{
    public function __construct(
        public PostTitle    $title,
        public ?PostContent $content,
        public ?PostCover   $cover,
        public PostAuthor   $author,
    )
    {
    }
}
