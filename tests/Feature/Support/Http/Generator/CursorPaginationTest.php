<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Render\ControllerRenderer;

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

it('documents the cursor envelope and parameter', function () {
    $rendered = renderCursorFixture();

    expect($rendered)
        ->toContain("new PaginatedResourceResponse(PostResource::class, paginationType: 'CursorPagination')")
        // There are no page numbers to ask for; the client echoes a cursor back instead.
        ->toContain("name: 'cursor'")
        ->not->toContain("name: 'page'");
});

it('keeps sorting and filtering under cursor pagination', function () {
    expect(renderCursorFixture())
        ->toContain("name: 'per_page'")
        ->toContain("name: 'sort'")
        ->toContain("name: 'filter[title]'");
});
