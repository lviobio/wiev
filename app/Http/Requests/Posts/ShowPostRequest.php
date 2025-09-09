<?php
declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ShowPostRequest extends AbstractPostInteractRequest
{
    public function authorize(): Response
    {
        $this->model = $this->findModel();

        return Gate::inspect('show', $this->model);
    }
}
