<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use Illuminate\Support\Str;

/**
 * The declaration a module's `Http/generator.php` returns.
 *
 * Names the controller to generate, the model it serves, and the endpoints it exposes.
 * Everything else - route prefixes, OpenAPI paths, operation ids, wording - is derived
 * from the model unless explicitly overridden.
 */
final class ControllerDefinition
{
    private const string DEFAULT_API_PREFIX = '/api/v1';

    private ?string $modelClass = null;

    private ?string $resourceClass = null;

    private ?Naming $naming = null;

    private ?string $routePrefix = null;

    private ?string $routeNamePrefix = null;

    private ?string $routeParameter = null;

    /** @var list<string>|null */
    private ?array $tags = null;

    /** @var list<string> */
    private array $security = ['bearerAuth'];

    /** @var list<string> */
    private array $middleware = [];

    private string $apiPrefix = self::DEFAULT_API_PREFIX;

    private bool $generateRoutes = true;

    private ?string $routesMarker = null;

    /** @var list<Endpoint> */
    private array $endpoints = [];

    private function __construct(public readonly string $controllerClass)
    {
    }

    /**
     * @param  class-string  $controllerClass  The controller to generate.
     */
    public static function make(string $controllerClass): self
    {
        return new self($controllerClass);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function model(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        $this->naming = new Naming($modelClass);

        return $this;
    }

    /**
     * @param  class-string<\App\Http\Resources\JsonResource>  $resourceClass
     */
    public function resource(string $resourceClass): self
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    /**
     * Override the noun derived from the model, e.g. `->noun('Entry', 'Entries')`.
     */
    public function noun(string $singular, ?string $plural = null): self
    {
        $this->naming = new Naming($singular);

        if ($plural !== null) {
            $this->routePrefix ??= Str::kebab($plural);
        }

        return $this;
    }

    public function tag(string ...$tags): self
    {
        $this->tags = array_values($tags);

        return $this;
    }

    public function routePrefix(string $prefix): self
    {
        $this->routePrefix = $prefix;

        return $this;
    }

    public function routeNamePrefix(string $prefix): self
    {
        $this->routeNamePrefix = $prefix;

        return $this;
    }

    public function routeParameter(string $parameter): self
    {
        $this->routeParameter = $parameter;

        return $this;
    }

    public function apiPrefix(string $prefix): self
    {
        $this->apiPrefix = rtrim($prefix, '/');

        return $this;
    }

    public function security(string ...$schemes): self
    {
        $this->security = array_values($schemes);

        return $this;
    }

    public function public(): self
    {
        $this->security = [];

        return $this;
    }

    public function middleware(string ...$middleware): self
    {
        $this->middleware = array_values($middleware);

        return $this;
    }

    /**
     * Leave `Http/routes.php` alone; routes are registered by hand.
     */
    public function withoutRoutes(): self
    {
        $this->generateRoutes = false;

        return $this;
    }

    /**
     * Name in the `@generated-routes` markers delimiting this controller's block.
     *
     * Defaults to the controller's short name, which keeps several controllers of the
     * same module from overwriting each other's block in a shared `routes.php`.
     */
    public function routesMarker(string $marker): self
    {
        $this->routesMarker = $marker;

        return $this;
    }

    public function getRoutesMarker(): string
    {
        return $this->routesMarker ?? $this->className();
    }

    public function endpoints(Endpoint ...$endpoints): self
    {
        $this->endpoints = array_values($endpoints);

        return $this;
    }

    /** @return list<Endpoint> */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    public function getModelClass(): string
    {
        return $this->modelClass ?? throw GeneratorException::missingModel($this->controllerClass);
    }

    public function getResourceClass(): ?string
    {
        return $this->resourceClass;
    }

    public function naming(): Naming
    {
        return $this->naming ?? throw GeneratorException::missingModel($this->controllerClass);
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix ?? $this->naming()->kebabPlural;
    }

    public function getRouteNamePrefix(): string
    {
        return $this->routeNamePrefix ?? $this->getRoutePrefix() . '.';
    }

    public function getRouteParameter(): string
    {
        return $this->routeParameter ?? $this->naming()->snakeSingular;
    }

    /** @return list<string> */
    public function getTags(): array
    {
        return $this->tags ?? [$this->naming()->kebabPlural];
    }

    /** @return list<string> */
    public function getSecurity(): array
    {
        return $this->security;
    }

    /** @return list<string> */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getApiPrefix(): string
    {
        return $this->apiPrefix;
    }

    public function shouldGenerateRoutes(): bool
    {
        return $this->generateRoutes;
    }

    /**
     * Full documented path for an endpoint, e.g. `/api/v1/posts/{post}`.
     */
    public function pathFor(Endpoint $endpoint): string
    {
        $uri = $endpoint->resolvedUri($this->getRouteParameter());
        $path = $this->apiPrefix . '/' . $this->getRoutePrefix();

        return $uri === '' ? $path : $path . '/' . $uri;
    }

    public function namespace(): string
    {
        $position = strrpos($this->controllerClass, '\\');

        return $position === false ? '' : substr($this->controllerClass, 0, $position);
    }

    public function className(): string
    {
        return class_basename($this->controllerClass);
    }

    /**
     * Absolute path of the controller file, resolved from the PSR-4 `App\` root.
     */
    public function controllerPath(): string
    {
        return app_path(str_replace('\\', '/', Str::after($this->controllerClass, 'App\\')) . '.php');
    }

    /**
     * Absolute path of the module's route file - a sibling of the `Http/` directory
     * the controller lives in, matching the existing `Http/routes.php` convention.
     */
    public function routesPath(): string
    {
        $controllerNamespace = str_replace('\\', '/', Str::after($this->namespace(), 'App\\'));

        return app_path(Str::before($controllerNamespace, '/Controllers') . '/routes.php');
    }

    /**
     * Name used in route markers and command output, e.g. `Post`.
     */
    public function moduleName(): string
    {
        return $this->naming()->studlySingular;
    }
}
