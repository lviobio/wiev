<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\Http\Generator\GeneratorException;
use App\Support\Spatie\QueryBuilder\AllowedFilter as AppAllowedFilter;
use App\Support\Spatie\QueryBuilder\QueryBuilder as AppQueryBuilder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use ReflectionProperty;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Filters\FiltersBelongsTo;
use Throwable;

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
                ->map(fn(AllowedFilter $filter): FilterIntrospection => $this->describeFilter(
                    $filter,
                    $query->getEloquentBuilder()->getModel(),
                ))
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

    private function describeFilter(AllowedFilter $filter, Model $model): FilterIntrospection
    {
        $strategy = $this->strategyOf($filter);

        return new FilterIntrospection(
            name: $filter->getName(),
            descriptors: $filter instanceof AppAllowedFilter ? $filter->openApiDescriptors() : [],
            filterClass: $strategy::class,
            relatedKeyType: $strategy instanceof FiltersBelongsTo
                ? $this->relatedKeyType($model, $this->internalNameOf($filter))
                : null,
        );
    }

    /**
     * The `Spatie\QueryBuilder\Filters\*` strategy backing a filter.
     */
    private function strategyOf(AllowedFilter $filter): object
    {
        $property = new ReflectionProperty(AllowedFilter::class, 'filterClass');

        return $property->getValue($filter);
    }

    /**
     * The relation name `FiltersBelongsTo` calls on the model at filter time - a filter
     * can rename itself for the client (`AllowedFilter::belongsTo('author', 'authorUser')`),
     * and it is this internal name, not the public one, that has to resolve to a relation.
     */
    private function internalNameOf(AllowedFilter $filter): string
    {
        $property = new ReflectionProperty(AllowedFilter::class, 'internalName');

        return $property->getValue($filter);
    }

    /**
     * `FiltersBelongsTo` matches records by the related model's own key, whatever type
     * that is - resolving the relation (not running it: building a `BelongsTo` reads no
     * rows) reads that type straight off the related model instead of assuming `integer`.
     *
     * A dotted name (`'author.department'`) walks each relation in turn, matching
     * {@see \Spatie\QueryBuilder\Filters\FiltersBelongsTo} itself. Falls back to null -
     * the caller assumes `integer`, same as before this existed - for anything that
     * doesn't resolve to a real relation chain.
     */
    private function relatedKeyType(Model $model, string $relationPath): ?string
    {
        try {
            $current = $model;

            foreach (explode('.', $relationPath) as $relationName) {
                if (!method_exists($current, $relationName)) {
                    return null;
                }

                $relation = $current->{$relationName}();

                if (!$relation instanceof Relation) {
                    return null;
                }

                $current = $relation->getRelated();
            }

            return $current->getKeyType() === 'int' ? 'integer' : 'string';
        } catch (Throwable) {
            // A documentation nicety isn't worth taking generation down over - the
            // caller's `integer` default is a reasonable enough fallback.
            return null;
        }
    }
}
