<?php
declare(strict_types=1);

namespace App\Http\Requests\Posts;

use App\Models\Post;

abstract class AbstractPostInteractRequest extends AbstractPostRequest
{
    public Post $model;
}
