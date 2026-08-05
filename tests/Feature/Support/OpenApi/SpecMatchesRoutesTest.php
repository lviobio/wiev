<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use OpenApi\Generator;

/**
 * The documented paths carry a hardcoded `/api/v1` prefix that nothing else validates.
 * This walks every documented operation and demands a real route behind it.
 */
it('documents only paths that are actually routed', function () {
    $specification = (new Generator())->generate([app_path()]);

    expect($specification?->paths)->not->toBeEmpty();

    // One URI is registered once per verb, so the methods have to be merged rather
    // than keyed - otherwise only the last registration for a path survives.
    $routes = collect(RouteFacade::getRoutes())
        ->groupBy(fn(Route $route): string => '/' . ltrim($route->uri(), '/'))
        ->map(fn($group): array => $group->flatMap(fn(Route $route): array => $route->methods())->unique()->all());

    foreach ($specification->paths as $path) {
        $methods = $routes->get($path->path);

        expect($methods)->not->toBeNull("No route registered for documented path [{$path->path}].");

        foreach (['get', 'post', 'put', 'patch', 'delete'] as $verb) {
            if ($path->{$verb} === Generator::UNDEFINED) {
                continue;
            }

            // A documented POST may stand in for a PUT/PATCH route: PHP cannot parse a
            // multipart body on those verbs, so uploads arrive through `_method` spoofing.
            $acceptable = $verb === 'post'
                ? ['POST', 'PUT', 'PATCH']
                : [strtoupper($verb)];

            expect(array_intersect($acceptable, $methods))
                ->not->toBeEmpty("Documented {$verb} {$path->path} has no matching route.");
        }
    }
});
