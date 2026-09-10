<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use App\Support\Http\Generator\Introspection\FilterIntrospection;
use App\Support\Http\Generator\Introspection\QueryDescriptor;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\FilterParameterFactory;
use App\Support\Spatie\QueryBuilder\FilterParameterDescriptor;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * Expands a vendor-extension marker left by the HTTP layer generator
 * ({@see \App\Support\Http\Generator\OpenApi\OperationFactory}) into the query
 * parameters a listing endpoint actually takes - `page`/`per_page`, `sort`, and one
 * parameter per `AllowedFilter` - instead of the generated controller enumerating them
 * as a literal array.
 *
 * The point is the same one `new OA\Schema(ref: XData::class)` already makes for
 * request bodies: a query class's `allowedFilters()`/`allowedSorts()` are the one
 * source of truth, read fresh whenever the spec is built, so adding a filter there
 * shows up in the documentation without a `http:generate` run to keep in sync.
 *
 * The mechanical parts of {@see \App\Support\Http\Generator\OpenApi\QueryParametersFactory}
 * (`page`/`cursor`/`per_page`/`sort`) are mirrored here rather than shared, because that
 * class builds printable {@see \App\Support\Http\Generator\Php\Expr} trees, not real
 * annotation objects. The one piece that carries actual judgment - which strategy class
 * documents as what - stays a single source of truth in
 * {@see FilterParameterFactory::describe()}, reused as-is.
 */
final class ListingQueryParameters
{
    public const X_QUERY_PARAMS_REF = 'query-params-ref';
    public const X_QUERY_PARAMS_CURSOR = 'query-params-cursor';

    public function __construct(
        private readonly QueryIntrospector $queries,
        private readonly FilterParameterFactory $filters = new FilterParameterFactory(),
    ) {
    }

    public function __invoke(Analysis $analysis): void
    {
        foreach ($analysis->getAnnotationsOfType(OA\Operation::class) as $operation) {
            if ($operation->x === Generator::UNDEFINED || !array_key_exists(self::X_QUERY_PARAMS_REF, $operation->x)) {
                continue;
            }

            $this->expand($operation);
        }
    }

    private function expand(OA\Operation $operation): void
    {
        /** @var class-string $queryClass */
        $queryClass = $operation->x[self::X_QUERY_PARAMS_REF];
        $cursor = (bool)($operation->x[self::X_QUERY_PARAMS_CURSOR] ?? false);

        $query = $this->queries->describe($queryClass);

        $listing = [$cursor ? $this->cursor() : $this->page(), $this->perPage($query)];

        if ($query->sorts !== []) {
            $listing[] = $this->sort($query);
        }

        $filterParameter = (string)config('query-builder.parameters.filter', 'filter');

        foreach ($query->filters as $filter) {
            foreach ($this->filters->describe($filter) as $descriptor) {
                $listing[] = $this->toParameter($descriptor, $filter, $filterParameter);
            }
        }

        $this->mergeParameters($operation, $listing);
        $this->cleanUp($operation);
    }

    /**
     * Keeps whatever path/extra parameters the generator already wrote, inserting the
     * listing parameters between the two - path parameters first, exactly as a
     * hand-composed `parameters:` array would read.
     *
     * @param  list<OA\Parameter>  $listing
     */
    private function mergeParameters(OA\Operation $operation, array $listing): void
    {
        $existing = $operation->parameters === Generator::UNDEFINED ? [] : $operation->parameters;

        $path = array_values(array_filter($existing, static fn (OA\Parameter $p): bool => $p->in === 'path'));
        $rest = array_values(array_filter($existing, static fn (OA\Parameter $p): bool => $p->in !== 'path'));

        $operation->parameters = [...$path, ...$listing, ...$rest];
    }

    private function cleanUp(OA\Operation $operation): void
    {
        unset($operation->x[self::X_QUERY_PARAMS_REF], $operation->x[self::X_QUERY_PARAMS_CURSOR]);

        if ($operation->x === []) {
            $operation->x = Generator::UNDEFINED;
        }
    }

    private function page(): OA\QueryParameter
    {
        return new OA\QueryParameter([
            'name' => 'page',
            'schema' => new OA\Schema(['type' => 'integer', 'default' => 1, 'minimum' => 1]),
        ]);
    }

    /**
     * Cursor pagination has no page numbers - the client echoes back an opaque cursor.
     */
    private function cursor(): OA\QueryParameter
    {
        return new OA\QueryParameter([
            'name' => 'cursor',
            'description' => 'Opaque cursor from `meta.next_cursor` of the previous page',
            'schema' => new OA\Schema(['type' => 'string']),
        ]);
    }

    private function perPage(QueryDescriptor $query): OA\QueryParameter
    {
        return new OA\QueryParameter([
            'name' => 'per_page',
            'schema' => new OA\Schema([
                'type' => 'integer',
                'default' => $query->allowedPerPage[0] ?? 15,
                'enum' => $query->allowedPerPage,
            ]),
        ]);
    }

    private function sort(QueryDescriptor $query): OA\QueryParameter
    {
        $enum = [];

        foreach ($query->sorts as $sort) {
            $enum[] = $sort;
            $enum[] = '-'.$sort;
        }

        return new OA\QueryParameter([
            'name' => (string)config('query-builder.parameters.sort', 'sort'),
            'description' => 'Sort field, prefix with `-` for descending order',
            'schema' => new OA\Schema([
                'type' => 'string',
                'default' => $query->defaultSort ?? $query->sorts[0],
                'enum' => $enum,
            ]),
        ]);
    }

    private function toParameter(FilterParameterDescriptor $descriptor, FilterIntrospection $filter, string $filterParameter): OA\QueryParameter
    {
        $schema = ['type' => $descriptor->type];

        if ($descriptor->format !== null) {
            $schema['format'] = $descriptor->format;
        }

        if ($descriptor->enum !== null) {
            $schema['enum'] = $descriptor->enum;
        }

        $data = ['name' => $descriptor->parameterName($filterParameter, $filter->name)];

        if ($descriptor->description !== null) {
            $data['description'] = $descriptor->description;
        }

        $data['schema'] = new OA\Schema($schema);

        return new OA\QueryParameter($data);
    }
}
