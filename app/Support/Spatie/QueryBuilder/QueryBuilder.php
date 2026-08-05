<?php
declare(strict_types=1);

namespace App\Support\Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    protected array $allowedPerPage = [15, 25, 50, 100];

    /**
     * Names of the sorts passed to `defaultSort()` / `defaultSorts()`.
     *
     * The parent only applies them and returns `$this`, so without recording them
     * here the configured default is unrecoverable - which the HTTP-layer generator
     * needs in order to document the `sort` parameter's default value.
     *
     * @var list<string>
     */
    protected array $defaultSortNames = [];

    public function defaultSorts(AllowedSort|string ...$sorts): static
    {
        // Record before delegating: the parent short-circuits when the request
        // already carries a sort, and would never reach the assignment.
        $this->defaultSortNames = array_map(
            static fn(AllowedSort|string $sort): string => $sort instanceof AllowedSort
                ? $sort->getName()
                : ltrim($sort, '-'),
            $sorts,
        );

        return parent::defaultSorts(...$sorts);
    }

    /**
     * @return Collection<int, \Spatie\QueryBuilder\AllowedFilter>
     */
    public function getAllowedFilters(): Collection
    {
        // Typed protected property on the parent, left uninitialised until
        // `allowedFilters()` is called. `??` is isset-based and does not throw.
        return $this->allowedFilters ?? collect();
    }

    /**
     * @return Collection<int, AllowedSort>
     */
    public function getAllowedSorts(): Collection
    {
        return $this->allowedSorts ?? collect();
    }

    /**
     * @return list<string>
     */
    public function getDefaultSortNames(): array
    {
        return $this->defaultSortNames;
    }

    /**
     * @return list<int>
     */
    public function getAllowedPerPage(): array
    {
        return $this->allowedPerPage;
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        return parent::paginate(perPage: $perPage ?? $this->resolvePerPage());
    }

    public function cursorPaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        return parent::cursorPaginate(perPage: $perPage ?? $this->resolvePerPage());
    }

    protected function resolvePerPage(): int
    {
        $perPage = $this->request->integer('per_page', head($this->allowedPerPage));

        if (!in_array($perPage, $this->allowedPerPage, true)) {
            $perPage = head($this->allowedPerPage);
        }

        return $perPage;
    }
}
