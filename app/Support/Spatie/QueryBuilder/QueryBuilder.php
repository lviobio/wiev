<?php
declare(strict_types=1);

namespace App\Support\Spatie\QueryBuilder;

use Spatie\QueryBuilder\QueryBuilder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    protected array $allowedPerPage = [15, 25, 50, 100];

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        if (!isset($perPage)) {
            $perPage = $this->request->integer('per_page', 15);

            if (!in_array($perPage, $this->allowedPerPage, true)) {
                $perPage = head($this->allowedPerPage);
            }
        }

        return parent::paginate(perPage: $perPage);
    }
}
