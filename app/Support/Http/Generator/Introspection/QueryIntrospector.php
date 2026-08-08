<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\Http\Generator\GeneratorException;
use App\Support\Spatie\QueryBuilder\AllowedFilter as AppAllowedFilter;
use App\Support\Spatie\QueryBuilder\QueryBuilder as AppQueryBuilder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use ReflectionProperty;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * Reads a listing query's allowed sorts, filters and page sizes.
 *
 * The query has to be instantiated rather than statically analysed: the allow-lists are
 * assembled by fluent calls in its constructor. Building the Eloquent builder resolves a
 * database Connection but never opens a PDO handle, so this is safe in console and CI.
 */
final class QueryIntrospector
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param  class-string  $queryClass
     */
    public function describe(string $queryClass): QueryDescriptor
    {
        if (!is_subclass_of($queryClass, AppQueryBuilder::class)) {
            throw GeneratorException::queryIsNotIntrospectable($queryClass);
        }

        $query = $this->resolve($queryClass);

        return new QueryDescriptor(
            sorts: $query->getAllowedSorts()
                ->map(static fn(AllowedSort $sort): string => $sort->getName())
                ->values()
                ->all(),
            defaultSort: $query->getDefaultSortNames()[0] ?? null,
            filters: $query->getAllowedFilters()
                ->map($this->describeFilter(...))
                ->values()
                ->all(),
            allowedPerPage: $query->getAllowedPerPage(),
        );
    }

    /**
     * @param  class-string<AppQueryBuilder>  $queryClass
     */
    private function resolve(string $queryClass): AppQueryBuilder
    {
        $previous = $this->container->bound('request') ? $this->container->make('request') : null;

        // A deterministic empty request: the query reads it while assembling its
        // allow-lists, and whatever the caller's request happened to look like must
        // not leak into the generated documentation. `QueryBuilderRequest` is a plain
        // bind that re-derives from this one, so swapping it here is enough.
        $this->container->instance('request', Request::create('/', 'GET'));

        try {
            return $this->container->make($queryClass);
        } finally {
            // Generation also runs inside tests and can run inside a request; leaving
            // the stand-in bound would quietly corrupt whatever executes next.
            if ($previous === null) {
                $this->container->forgetInstance('request');
            } else {
                $this->container->instance('request', $previous);
            }
        }
    }

    private function describeFilter(AllowedFilter $filter): FilterIntrospection
    {
        return new FilterIntrospection(
            name: $filter->getName(),
            descriptors: $filter instanceof AppAllowedFilter ? $filter->openApiDescriptors() : [],
            filterClass: $this->strategyClassOf($filter),
        );
    }

    /**
     * The `Spatie\QueryBuilder\Filters\*` strategy backing a filter.
     */
    private function strategyClassOf(AllowedFilter $filter): string
    {
        $property = new ReflectionProperty(AllowedFilter::class, 'filterClass');

        return $property->getValue($filter)::class;
    }
}
