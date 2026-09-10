<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Render\ControllerRenderer;
use App\Support\OpenApi\Swagger\ListingQueryParameters;

function renderCursorFixture(): string
{
    $definition = app(DefinitionLocator::class)
        ->loadFile(base_path('tests/Fixtures/Http/Generator/cursor-pagination.php'))[0];

    return (new ControllerRenderer(new OperationFactory(app(QueryIntrospector::class))))
        ->render($definition)
        ->contents;
}

it('paginates by cursor when asked to', function () {
    expect(renderCursorFixture())
        ->toContain('return PostResource::collection($query->cursorPaginate());')
        ->not->toContain('$query->paginate()');
});

it('documents the cursor envelope and marks the operation cursor-paginated', function () {
    $rendered = renderCursorFixture();

    // The parameters themselves aren't written here at all - ListingQueryParameters
    // expands the `x` marker into `cursor`/`per_page`/`sort`/`filter[...]` when the
    // spec is actually built. See ListingQueryParametersTest for that expansion,
    // including that cursor mode drops `page` in favour of `cursor`.
    expect($rendered)
        ->toContain("new PaginatedResourceResponse(PostResource::class, paginationType: 'CursorPagination')")
        ->toContain("'" . ListingQueryParameters::X_QUERY_PARAMS_CURSOR . "' => true")
        ->toContain("'" . ListingQueryParameters::X_QUERY_PARAMS_REF . "' => PostIndexQuery::class")
        ->not->toContain('parameters:');
});
