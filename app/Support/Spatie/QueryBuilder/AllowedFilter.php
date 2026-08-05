<?php
declare(strict_types=1);

namespace App\Support\Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter as BaseAllowedFilter;

final class AllowedFilter extends BaseAllowedFilter
{
    /**
     * OpenAPI parameters this filter expands into.
     *
     * Only needed for filters the generator cannot recognise on its own: everything
     * built on top of `callback()` collapses into the same `FiltersCallback`, and the
     * columns it touches are captured inside a closure.
     *
     * @var list<FilterParameterDescriptor>
     */
    private array $openApiDescriptors = [];

    public static function dateRange(string $column, ?string $filterName = null): static
    {
        $filterName ??= $column;

        return self::callback($filterName, function (Builder $query, array $value) use ($column): void {
            if (filled($value['from'] ?? null)) {
                $query->where($column, '>=', Carbon::createFromTimestampMs((int)$value['from']));
            }

            if (filled($value['to'] ?? null)) {
                $query->where($column, '<=', Carbon::createFromTimestampMs((int)$value['to']));
            }
        })->openApi(
            new FilterParameterDescriptor(
                type: 'integer',
                format: 'int64',
                description: 'Unix timestamp in milliseconds',
                suffixPath: ['from'],
            ),
            new FilterParameterDescriptor(
                type: 'integer',
                format: 'int64',
                description: 'Unix timestamp in milliseconds',
                suffixPath: ['to'],
            ),
        );
    }

    public static function search(string|array $columns, string $filterName = 'search'): static
    {
        $columns = Arr::wrap($columns);

        return self::callback($filterName, function (Builder $query, string $value) use ($columns): void {
            $searchWords = array_filter(array_map('trim', explode(' ', $value)));

            if (empty($searchWords)) {
                return;
            }

            $query->where(function (Builder $query) use ($searchWords, $columns) {
                foreach ($searchWords as $searchWord) {
                    $query->orWhere(function (Builder $q) use ($searchWord, $columns) {
                        collect($columns)->each(
                            fn($column) => $q->orWhereLike($q->qualifyColumn($column), "%{$searchWord}%")
                        );
                    });
                }
            });
        })->openApi(
            new FilterParameterDescriptor(
                description: 'Partial match against ' . Arr::join($columns, ', ', ' and '),
            ),
        );
    }

    /**
     * Declare how this filter should be documented.
     */
    public function openApi(FilterParameterDescriptor ...$descriptors): static
    {
        $this->openApiDescriptors = array_values($descriptors);

        return $this;
    }

    /**
     * @return list<FilterParameterDescriptor>
     */
    public function openApiDescriptors(): array
    {
        return $this->openApiDescriptors;
    }
}
