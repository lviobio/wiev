<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Requests;

use App\Modules\Post\Models\Post;

abstract class AbstractPostInteractRequest extends AbstractPostRequest
{
    public Post $model;
}
