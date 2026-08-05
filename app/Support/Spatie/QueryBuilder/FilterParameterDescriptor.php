<?php
declare(strict_types=1);

namespace App\Support\Spatie\QueryBuilder;

/**
 * Describes one OpenAPI query parameter produced by an allowed filter.
 *
 * Filters built through `AllowedFilter::callback()` all collapse into the same
 * `FiltersCallback` instance, and the columns they touch are captured inside a
 * closure - so the HTTP-layer generator cannot tell `search()` from `dateRange()`
 * by reflection alone. Attaching descriptors at construction time is what keeps
 * the generated documentation faithful.
 *
 * A single filter may expand into several parameters, which is why the descriptor
 * carries a path suffix: `dateRange('created_at')` yields
 * `filter[created_at][from]` and `filter[created_at][to]`.
 */
final readonly class FilterParameterDescriptor
{
    /**
     * @param  list<string>  $suffixPath  Extra bracket segments appended after the filter name.
     * @param  list<string|int>|null  $enum
     */
    public function __construct(
        public string $type = 'string',
        public ?string $format = null,
        public ?string $description = null,
        public array $suffixPath = [],
        public ?array $enum = null,
    ) {
    }

    /**
     * Full query parameter name, e.g. `filter[created_at][from]`.
     */
    public function parameterName(string $filterParameter, string $filterName): string
    {
        $name = "{$filterParameter}[{$filterName}]";

        foreach ($this->suffixPath as $segment) {
            $name .= "[{$segment}]";
        }

        return $name;
    }
}
