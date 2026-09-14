<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\UpdatePost;

use App\Models\User;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostCover;
use App\Modules\Post\Domain\VO\PostIdentifier;
use App\Modules\Post\Domain\VO\PostTitle;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdatePostData extends Data
{
    public function __construct(
        public PostTitle               $title,
        public PostContent|null        $content,
        public PostCover|null|Optional $cover,
        public User                    $actorUser,
        public PostIdentifier          $id,
    )
    {
    }
}
