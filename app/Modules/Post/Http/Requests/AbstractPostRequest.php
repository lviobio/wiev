<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Requests;

use App\Http\Requests\FormRequest;
use App\Modules\Post\Models\Post;
use Illuminate\Auth\Access\Response;

abstract class AbstractPostRequest extends FormRequest
{
    abstract public function authorize(): Response;

    protected function findModel(): Post
    {
        return Post::query()
            ->withTrashed()
            ->findOrFail($this->route('post'));
    }
}
