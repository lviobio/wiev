<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\OpenApi\Swagger\ListingQueryParameters;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * {@see ListingQueryParameters} against a bare `x`-marked operation, independent of any
 * particular generated controller - the real one is exercised end to end by
 * {@see SpecMatchesRoutesTest} and by `http:generate --check`, but neither of those
 * pins down cursor mode: nothing committed actually uses it, so it only gets covered
 * by building the marker here directly.
 */
function markedOperation(bool $cursor = false): OA\Get
{
    $x = [
        ListingQueryParameters::X_QUERY_PARAMS_REF => PostIndexQuery::class,
    ];

    if ($cursor) {
        $x[ListingQueryParameters::X_QUERY_PARAMS_CURSOR] = true;
    }

    return new OA\Get(['x' => $x]);
}

function expand(OA\Get $operation): void
{
    $analysis = new Analysis([$operation], new Context());

    (new ListingQueryParameters(app(QueryIntrospector::class)))($analysis);
}

it('expands page, per_page, sort and one parameter per allowed filter', function () {
    $operation = markedOperation();

    expand($operation);

    $names = array_map(static fn (OA\Parameter $p): string => $p->name, $operation->parameters);

    expect($names)->toBe([
        'page',
        'per_page',
        'sort',
        'filter[title]',
        'filter[trashed]',
        'filter[search]',
        'filter[created_at][from]',
        'filter[created_at][to]',
    ]);
});

it('switches to a cursor parameter instead of page when cursor-paginated', function () {
    $operation = markedOperation(cursor: true);

    expand($operation);

    $names = array_map(static fn (OA\Parameter $p): string => $p->name, $operation->parameters);

    expect($names)->toContain('cursor')->not->toContain('page');
});

it('reads sort and filter shapes straight off the query class', function () {
    $operation = markedOperation();

    expand($operation);

    $byName = [];

    foreach ($operation->parameters as $parameter) {
        $byName[$parameter->name] = $parameter;
    }

    expect($byName['per_page']->schema->enum)->toBe([15, 25, 50, 100])
        ->and($byName['sort']->schema->enum)->toBe(['id', '-id', 'title', '-title', 'created_at', '-created_at'])
        ->and($byName['filter[trashed]']->schema->enum)->toBe(['with', 'only'])
        ->and($byName['filter[trashed]']->description)->toBe('Include soft-deleted records')
        ->and($byName['filter[created_at][from]']->schema->type)->toBe('integer')
        ->and($byName['filter[created_at][from]']->schema->format)->toBe('int64');
});

it('cleans up the marker so it never reaches the spec', function () {
    $operation = markedOperation();

    expand($operation);

    expect($operation->x)->toBe(Generator::UNDEFINED);
});

it('keeps path parameters first and extra parameters last', function () {
    $operation = markedOperation();
    $operation->parameters = [
        new OA\PathParameter(['name' => 'post']),
        new OA\QueryParameter(['name' => 'with_drafts']),
    ];

    expand($operation);

    $names = array_map(static fn (OA\Parameter $p): string => $p->name, $operation->parameters);

    expect($names)->toBe(['post', 'page', 'per_page', 'sort', 'filter[title]', 'filter[trashed]', 'filter[search]', 'filter[created_at][from]', 'filter[created_at][to]', 'with_drafts']);
});
