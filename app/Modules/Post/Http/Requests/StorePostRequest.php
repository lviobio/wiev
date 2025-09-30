<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Requests;

use App\Modules\Post\Models\Post;
use App\Support\Validation\ImageRule;
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
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', ...ImageRule::make()->toArray()]
        ];
    }
}
