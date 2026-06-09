<?php
declare(strict_types=1);

namespace App\Support\Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter as BaseAllowedFilter;

final class AllowedFilter extends BaseAllowedFilter
{
    public static function dateRange(string $column, ?string $filterName = null): BaseAllowedFilter
    {
        $filterName ??= $column;

        return self::callback($filterName, function (Builder $query, array $value) use ($column): void {
            if (filled($value['from'] ?? null)) {
                $query->where($column, '>=', Carbon::createFromTimestampMs((int)$value['from']));
            }

            if (filled($value['to'] ?? null)) {
                $query->where($column, '<=', Carbon::createFromTimestampMs((int)$value['to']));
            }
        });
    }

    public static function search(string|array $columns, string $filterName = 'search'): BaseAllowedFilter
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
        });
    }
}
