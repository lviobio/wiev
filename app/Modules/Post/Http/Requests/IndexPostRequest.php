<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Requests;

use App\Modules\Post\Models\Post;
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
