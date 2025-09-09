<?php
declare(strict_types=1);

namespace App\Http\Requests\Posts;

use App\Models\Post;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class IndexPostRequest extends AbstractPostRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('index', Post::class);
    }

    public function rules(): array
    {
        return [
            'filters.search' => ['nullable', 'string', 'max:255'],
            ...self::basePaginationRules(
                withTrashedFilter: true,
            ),
        ];
    }
}
