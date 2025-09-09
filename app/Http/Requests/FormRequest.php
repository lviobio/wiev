<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Validation\Rule;

abstract class FormRequest extends BaseFormRequest
{
    protected static function basePaginationRules(
        bool $withTrashedFilter = false,
    ): array
    {
        $result = [
            'per_page' => ['integer', 'min:1', 'max:100'],
            'page' => ['integer', 'min:1'],
        ];

        if ($withTrashedFilter) {
            $result['filters.trashed'] = ['nullable', 'string', Rule::in(['with', 'only'])];
        }

        return $result;
    }
}
