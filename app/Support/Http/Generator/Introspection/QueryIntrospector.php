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
        // A deterministic empty request: the query reads it while assembling its
        // allow-lists, and whatever the CLI invocation happened to look like must
        // not leak into the generated documentation.
        $this->container->instance('request', Request::create('/', 'GET'));
        $this->container->forgetInstance(\Spatie\QueryBuilder\QueryBuilderRequest::class);

        return $this->container->make($queryClass);
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
