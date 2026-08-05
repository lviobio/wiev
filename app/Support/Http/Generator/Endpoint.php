<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use App\Support\Data\Filling\DataPropertyFiller;
use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\Php\Expr;

/**
 * One generated controller method plus the route that reaches it.
 *
 * Only the Action (or, for listings, the Query) and the fillers are declared; the Data
 * class, request body, path parameters and responses are all inferred from them.
 */
final class Endpoint
{
    /**
     * Placeholder in a URI standing for the definition's route parameter, so presets
     * do not need to know the model name: `'{model}/publish'` becomes `'{post}/publish'`.
     */
    public const string MODEL_PARAMETER = '{model}';

    /** @var list<DataPropertyFiller> */
    private array $fillers = [];

    private ?string $summary = null;

    private ?string $description = null;

    private ?string $operationId = null;

    /** @var list<string>|null */
    private ?array $tags = null;

    /** @var list<string>|null */
    private ?array $security = null;

    private bool $deprecated = false;

    private ?string $docblock = null;

    /** @var list<Expr> */
    private array $extraParameters = [];

    /** @var list<Expr> */
    private array $extraResponses = [];

    private ?Expr $openApiOverride = null;

    private bool $documented = true;

    private bool $requestBody = true;

    private ?string $mediaType = null;

    private ?HttpMethod $documentedAs = null;

    private ?string $bodyOverride = null;

    /** @var list<string> */
    private array $uses = [];

    /** @var list<string> */
    private array $middleware = [];

    private function __construct(
        public readonly EndpointKind $kind,
        public readonly HttpMethod $method,
        public readonly string $uri,
        public readonly string $controllerMethod,
        public readonly string $routeName,
        public readonly ?string $actionClass = null,
        public readonly ?string $queryClass = null,
    ) {
    }

    public static function index(?string $queryClass = null, ?string $actionClass = null): self
    {
        return new self(
            kind: EndpointKind::Index,
            method: HttpMethod::Get,
            uri: '',
            controllerMethod: 'index',
            routeName: 'index',
            actionClass: $actionClass,
            queryClass: $queryClass,
        );
    }

    public static function show(string $actionClass): self
    {
        return new self(
            kind: EndpointKind::Show,
            method: HttpMethod::Get,
            uri: self::MODEL_PARAMETER,
            controllerMethod: 'show',
            routeName: 'show',
            actionClass: $actionClass,
        );
    }

    public static function store(string $actionClass): self
    {
        return new self(
            kind: EndpointKind::Store,
            method: HttpMethod::Post,
            uri: '',
            controllerMethod: 'store',
            routeName: 'store',
            actionClass: $actionClass,
        );
    }

    public static function update(string $actionClass): self
    {
        return new self(
            kind: EndpointKind::Update,
            method: HttpMethod::Put,
            uri: self::MODEL_PARAMETER,
            controllerMethod: 'update',
            routeName: 'update',
            actionClass: $actionClass,
        );
    }

    public static function destroy(string $actionClass): self
    {
        return new self(
            kind: EndpointKind::Destroy,
            method: HttpMethod::Delete,
            uri: self::MODEL_PARAMETER,
            controllerMethod: 'destroy',
            routeName: 'destroy',
            actionClass: $actionClass,
        );
    }

    /**
     * An endpoint outside the five CRUD shapes.
     */
    public static function make(
        HttpMethod $method,
        string $uri,
        string $controllerMethod,
        ?string $routeName = null,
        ?string $actionClass = null,
        ?string $queryClass = null,
    ): self {
        return new self(
            kind: EndpointKind::Custom,
            method: $method,
            uri: $uri,
            controllerMethod: $controllerMethod,
            routeName: $routeName ?? $controllerMethod,
            actionClass: $actionClass,
            queryClass: $queryClass,
        );
    }

    /**
     * How the Data object's non-body properties are populated at the HTTP boundary.
     *
     * Re-emitted verbatim onto the generated parameter, so the existing
     * {@see \App\Support\Routing\DataFillingControllerDispatcher} keeps doing the work.
     */
    public function fill(DataPropertyFiller ...$fillers): self
    {
        $this->fillers = array_values($fillers);

        return $this;
    }

    public function summary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function operationId(string $operationId): self
    {
        $this->operationId = $operationId;

        return $this;
    }

    public function tags(string ...$tags): self
    {
        $this->tags = array_values($tags);

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

    public function deprecated(bool $deprecated = true): self
    {
        $this->deprecated = $deprecated;

        return $this;
    }

    public function docblock(string $docblock): self
    {
        $this->docblock = $docblock;

        return $this;
    }

    public function addParameters(Expr ...$parameters): self
    {
        $this->extraParameters = [...$this->extraParameters, ...array_values($parameters)];

        return $this;
    }

    public function addResponses(Expr ...$responses): self
    {
        $this->extraResponses = [...$this->extraResponses, ...array_values($responses)];

        return $this;
    }

    /**
     * Replace the whole derived operation attribute; the method itself is still generated.
     */
    public function openApi(Expr $operation): self
    {
        $this->openApiOverride = $operation;

        return $this;
    }

    public function withoutOpenApi(): self
    {
        $this->documented = false;

        return $this;
    }

    public function withoutRequestBody(): self
    {
        $this->requestBody = false;

        return $this;
    }

    public function mediaType(string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    /**
     * Document this endpoint under a different verb than the route registers.
     */
    public function documentAs(HttpMethod $method): self
    {
        $this->documentedAs = $method;

        return $this;
    }

    /**
     * Hand-written method body, for endpoints the presets cannot express.
     */
    public function body(string $php, string ...$uses): self
    {
        $this->bodyOverride = $php;
        $this->uses = [...$this->uses, ...array_values($uses)];

        return $this;
    }

    public function uses(string ...$fqcn): self
    {
        $this->uses = [...$this->uses, ...array_values($fqcn)];

        return $this;
    }

    public function middleware(string ...$middleware): self
    {
        $this->middleware = array_values($middleware);

        return $this;
    }

    /** @return list<DataPropertyFiller> */
    public function fillers(): array
    {
        return $this->fillers;
    }

    /**
     * Route parameters this endpoint reads, in declaration order.
     *
     * @return list<string>
     */
    public function routeParameters(): array
    {
        $parameters = [];

        foreach ($this->fillers as $filler) {
            if ($filler instanceof FillFromRouteParameter) {
                $parameters[] = $filler->parameter();
            }
        }

        return $parameters;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOperationId(): ?string
    {
        return $this->operationId;
    }

    /** @return list<string>|null */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /** @return list<string>|null */
    public function getSecurity(): ?array
    {
        return $this->security;
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function getDocblock(): ?string
    {
        return $this->docblock;
    }

    /** @return list<Expr> */
    public function getExtraParameters(): array
    {
        return $this->extraParameters;
    }

    /** @return list<Expr> */
    public function getExtraResponses(): array
    {
        return $this->extraResponses;
    }

    public function getOpenApiOverride(): ?Expr
    {
        return $this->openApiOverride;
    }

    public function isDocumented(): bool
    {
        return $this->documented;
    }

    public function wantsRequestBody(): bool
    {
        return $this->requestBody;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function getDocumentedAs(): ?HttpMethod
    {
        return $this->documentedAs;
    }

    public function getBodyOverride(): ?string
    {
        return $this->bodyOverride;
    }

    /** @return list<string> */
    public function getUses(): array
    {
        return $this->uses;
    }

    /** @return list<string> */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * The route URI with {@see self::MODEL_PARAMETER} resolved.
     */
    public function resolvedUri(string $routeParameter): string
    {
        return str_replace(self::MODEL_PARAMETER, '{' . $routeParameter . '}', $this->uri);
    }
}
