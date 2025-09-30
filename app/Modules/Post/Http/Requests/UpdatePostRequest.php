<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Requests;

use App\Support\Validation\ImageRule;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class UpdatePostRequest extends AbstractPostInteractRequest
{
    public function authorize(): Response
    {
        $this->model = $this->findModel();

        return Gate::inspect('update', $this->model);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', ...ImageRule::make()->toArray()]
        ];
    }
}
