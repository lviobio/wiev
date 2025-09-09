<?php
declare(strict_types=1);

namespace App\Http\Requests\Posts;

use App\Models\Post;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class StorePostRequest extends AbstractPostRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('store', Post::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
