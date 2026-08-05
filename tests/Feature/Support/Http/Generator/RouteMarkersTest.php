<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Modules\Post\Http\Controllers\PostController;
use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\Render\RoutesWriter;

/**
 * `Http/routes.php` stays hand-editable: the generator owns only the region between
 * its markers. These tests pin that contract down, since getting it wrong would
 * silently delete routes nobody generated.
 */
beforeEach(function () {
    $this->definition = app(DefinitionLocator::class)->forModule('PostController')[0];
    $this->routesPath = $this->definition->routesPath();
    $this->original = (string)file_get_contents($this->routesPath);
});

afterEach(function () {
    file_put_contents($this->routesPath, $this->original);
});

it('leaves a hand-written route outside the markers untouched', function () {
    $handWritten = "\nRoute::get('posts/export', [" . PostController::class . "::class, 'export'])->name('posts.export');\n";

    file_put_contents($this->routesPath, $this->original . $handWritten);

    $generated = (new RoutesWriter())->build($this->definition);

    expect($generated->contents)->toContain($handWritten)
        ->and($generated->contents)->toContain("Route::get('/', 'index')->name('index');");
});

it('rewrites only the marked region', function () {
    $tampered = str_replace(
        "Route::get('/', 'index')->name('index');",
        "Route::get('/', 'somethingElse')->name('nonsense');",
        $this->original,
    );

    file_put_contents($this->routesPath, $tampered);

    $generated = (new RoutesWriter())->build($this->definition);

    expect($generated->contents)->toBe($this->original);
});

it('appends a marked block when the file has never been generated into', function () {
    $withoutMarkers = preg_replace('/^\/\/ @generated-routes:.*$\n?/m', '', $this->original);

    file_put_contents($this->routesPath, (string)$withoutMarkers);

    $generated = (new RoutesWriter())->build($this->definition);

    // The pre-existing content survives; the generated block is added, not substituted.
    expect($generated->contents)->toContain("Route::group(['prefix' => '{post}'], function () {")
        ->and(substr_count($generated->contents, '@generated-routes:start PostController'))->toBe(1)
        ->and(substr_count($generated->contents, '@generated-routes:end PostController'))->toBe(1);
});

it('does not touch the routes file when routes are declared by hand', function () {
    $definition = clone $this->definition;

    expect($definition->withoutRoutes()->shouldGenerateRoutes())->toBeFalse();
});
