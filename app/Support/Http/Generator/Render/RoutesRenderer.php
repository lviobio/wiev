<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Render;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\Php\Printer;

/**
 * Renders the route registrations for a definition.
 *
 * Only the block itself is produced here - where it lands inside `Http/routes.php` is
 * {@see RoutesWriter}'s business, because that file stays hand-editable.
 */
final readonly class RoutesRenderer
{
    /**
     * The `Route::group(...)` block, without markers and without a trailing newline.
     */
    public function render(ControllerDefinition $definition, string $controllerAlias): string
    {
        $routeParameter = $definition->getRouteParameter();

        $group = [
            "'prefix' => '{$definition->getRoutePrefix()}'",
            "'as' => '{$definition->getRouteNamePrefix()}'",
            "'controller' => {$controllerAlias}::class",
        ];

        if ($definition->getMiddleware() !== []) {
            $middleware = implode(', ', array_map(
                static fn(string $name): string => "'{$name}'",
                $definition->getMiddleware(),
            ));

            $group[] = "'middleware' => [{$middleware}]";
        }

        [$rootEndpoints, $nested] = $this->partition($definition, $routeParameter);

        $body = array_map(
            function (Endpoint $endpoint) use ($routeParameter): string {
                $uri = $endpoint->resolvedUri($routeParameter);

                return Printer::indent(4) . $this->route($endpoint, $routeParameter, $uri === '' ? '/' : $uri);
            },
            $rootEndpoints,
        );

        foreach ($nested as $prefix => $endpoints) {
            $body[] = Printer::indent(4) . "Route::group(['prefix' => '{$prefix}'], function () {";

            foreach ($endpoints as $endpoint) {
                $uri = $this->stripPrefix($endpoint->resolvedUri($routeParameter), $prefix);

                $body[] = Printer::indent(8) . $this->route($endpoint, $routeParameter, $uri === '' ? '/' : $uri);
            }

            $body[] = Printer::indent(4) . '});';
        }

        return 'Route::group([' . implode(', ', $group) . '], function () {' . PHP_EOL
            . implode(PHP_EOL, $body) . PHP_EOL
            . '});';
    }

    /**
     * Split endpoints into those sitting at the collection root and those under the
     * `{model}` segment, reproducing the nested-group shape used across the codebase.
     *
     * @return array{list<Endpoint>, array<string, list<Endpoint>>}
     */
    private function partition(ControllerDefinition $definition, string $routeParameter): array
    {
        $modelPrefix = '{' . $routeParameter . '}';
        $root = [];
        $nested = [];

        foreach ($definition->getEndpoints() as $endpoint) {
            $uri = $endpoint->resolvedUri($routeParameter);

            if ($uri === $modelPrefix || str_starts_with($uri, $modelPrefix . '/')) {
                $nested[$modelPrefix][] = $endpoint;

                continue;
            }

            $root[] = $endpoint;
        }

        return [$root, $nested];
    }

    private function stripPrefix(string $uri, string $prefix): string
    {
        return ltrim(substr($uri, strlen($prefix)), '/');
    }

    private function route(Endpoint $endpoint, string $routeParameter, string $uri): string
    {
        $line = sprintf(
            "Route::%s('%s', '%s')->name('%s')",
            $endpoint->method->routeMethod(),
            $uri,
            $endpoint->controllerMethod,
            $endpoint->routeName,
        );

        foreach ($endpoint->getMiddleware() as $middleware) {
            $line .= "->middleware('{$middleware}')";
        }

        return $line . ';';
    }
}
