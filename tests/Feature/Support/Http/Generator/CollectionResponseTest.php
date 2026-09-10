<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Render\ControllerRenderer;

function renderCollectionFixture(): string
{
    $definition = app(DefinitionLocator::class)
        ->loadFile(base_path('tests/Fixtures/Http/Generator/action-collection.php'))[0];

    return new ControllerRenderer(new OperationFactory(app(QueryIntrospector::class)))
        ->render($definition)
        ->contents;
}

it('documents a collection coming from an Action without a pagination envelope', function () {
    // тело метода печатается без paginate(), значит и конверт не пагинированный
    expect(renderCollectionFixture())
        ->toContain('return PostFileResource::collection($action($data));')
        ->toContain('new ResourceCollectionResponse(PostFileResource::class)');
});

it('keeps the paginated envelope for a collection coming from a Query', function () {
    expect(renderCollectionFixture())
        ->toContain('return PostFileResource::collection($query->paginate());')
        ->toContain('new PaginatedResourceResponse(PostFileResource::class)');
});
