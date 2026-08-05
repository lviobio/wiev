<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\GeneratorException;

it('finds the declared controllers', function () {
    $definitions = app(DefinitionLocator::class)->all();

    expect($definitions)->not->toBeEmpty()
        ->and($definitions)->each->toBeInstanceOf(ControllerDefinition::class);
});

it('filters by module name or by controller name', function () {
    $locator = app(DefinitionLocator::class);

    expect($locator->forModule('Post'))->not->toBeEmpty()
        ->and($locator->forModule('postcontroller'))->toHaveCount(1)
        ->and($locator->forModule('PostController')[0]->className())->toBe('PostController');
});

it('rejects an unknown module', function () {
    app(DefinitionLocator::class)->forModule('NoSuchThing');
})->throws(GeneratorException::class);

it('accepts a declaration file returning several definitions', function () {
    // A module may expose more than one controller, so the file may return a list.
    $definitions = app(DefinitionLocator::class)
        ->loadFile(base_path('tests/Fixtures/Http/Generator/multiple-definitions.php'));

    expect($definitions)->toHaveCount(2)
        ->and($definitions)->each->toBeInstanceOf(ControllerDefinition::class);

    // Distinct markers are what keeps their route blocks from overwriting each other.
    expect($definitions[0]->getRoutesMarker())->toBe('PostController')
        ->and($definitions[1]->getRoutesMarker())->toBe('PostArchiveController');
});

it('rejects a declaration file returning something else', function () {
    app(DefinitionLocator::class)->loadFile(base_path('tests/Fixtures/Http/Generator/not-a-definition.php'));
})->throws(GeneratorException::class);
