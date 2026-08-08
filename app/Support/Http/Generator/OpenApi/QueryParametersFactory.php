<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\Introspection\QueryDescriptor;
use App\Support\Http\Generator\Naming;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use OpenApi\Attributes as OA;

/**
 * Pagination, sorting and filtering parameters of a listing endpoint.
 */
final class QueryParametersFactory
{
    /** @var list<string> */
    private array $warnings = [];

    public function __construct(private readonly FilterParameterFactory $filters = new FilterParameterFactory())
    {
    }

    /**
     * @return list<Expr>
     */
    public function build(QueryDescriptor $query, Naming $naming, bool $cursorPaginated = false): array
    {
        $parameters = [
            $cursorPaginated ? $this->cursor() : $this->page(),
            $this->perPage($query),
        ];

        if ($query->sorts !== []) {
            $parameters[] = $this->sort($query);
        }

        $filterParameter = (string)config('query-builder.parameters.filter', 'filter');

        foreach ($query->filters as $filter) {
            $parameters = [...$parameters, ...$this->filters->build($filter, $filterParameter, $naming)];
        }

        $this->warnings = $this->filters->warnings();

        return $parameters;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    private function page(): Expr
    {
        return new NewExpr(OA\QueryParameter::class, [
            'name' => new Literal('page'),
            'schema' => new NewExpr(OA\Schema::class, [
                'type' => new Literal('integer'),
                'default' => new Literal(1),
                'minimum' => new Literal(1),
            ]),
        ]);
    }

    /**
     * Cursor pagination has no page numbers - the client echoes back an opaque cursor.
     */
    private function cursor(): Expr
    {
        return new NewExpr(OA\QueryParameter::class, [
            'name' => new Literal('cursor'),
            'description' => new Literal('Opaque cursor from `meta.next_cursor` of the previous page'),
            'schema' => new NewExpr(OA\Schema::class, ['type' => new Literal('string')]),
        ]);
    }

    private function perPage(QueryDescriptor $query): Expr
    {
        return new NewExpr(OA\QueryParameter::class, [
            'name' => new Literal('per_page'),
            'schema' => new NewExpr(OA\Schema::class, [
                'type' => new Literal('integer'),
                'default' => new Literal($query->allowedPerPage[0] ?? 15),
                'enum' => new Literal($query->allowedPerPage),
            ]),
        ]);
    }

    private function sort(QueryDescriptor $query): Expr
    {
        $enum = [];

        foreach ($query->sorts as $sort) {
            $enum[] = $sort;
            $enum[] = '-' . $sort;
        }

        $schema = [
            'type' => new Literal('string'),
            'default' => new Literal($query->defaultSort ?? $query->sorts[0]),
            'enum' => new Literal($enum),
        ];

        return new NewExpr(OA\QueryParameter::class, [
            'name' => new Literal((string)config('query-builder.parameters.sort', 'sort')),
            'description' => new Literal('Sort field, prefix with `-` for descending order'),
            'schema' => new NewExpr(OA\Schema::class, $schema),
        ]);
    }
}
