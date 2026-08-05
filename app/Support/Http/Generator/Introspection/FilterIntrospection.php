<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\Spatie\QueryBuilder\FilterParameterDescriptor;

/**
 * One allowed filter, as much as reflection can tell about it.
 */
final readonly class FilterIntrospection
{
    /**
     * @param  list<FilterParameterDescriptor>  $descriptors  Empty when the filter did not
     *                                                        declare how to document itself.
     * @param  class-string  $filterClass  The `Spatie\QueryBuilder\Filters\*` strategy,
     *                                     used to guess a shape when no descriptor exists.
     */
    public function __construct(
        public string $name,
        public array $descriptors,
        public string $filterClass,
    ) {
    }
}
