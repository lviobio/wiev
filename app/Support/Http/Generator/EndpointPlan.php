<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use App\Support\Http\Generator\Introspection\CallbackSource;
use App\Support\Http\Generator\Introspection\ReturnKind;
use ReflectionParameter;

/**
 * An endpoint declaration resolved against the classes it names.
 *
 * Everything downstream - OpenAPI attributes, method signature, method body, route -
 * is rendered from this, so all reflection happens once, up front.
 */
final readonly class EndpointPlan
{
    /**
     * @param  class-string|null  $dataClass
     * @param  list<ReflectionParameter>  $bodyProperties
     * @param  list<string>  $pathParameters  Placeholders in the URI, in order.
     * @param  bool  $looksUpByRouteParameter  Whether the endpoint resolves a record from
     *                                         a route parameter, and so can answer "missing".
     * @param  list<string>  $warnings
     */
    public function __construct(
        public Endpoint $endpoint,
        public ?string $dataClass,
        public ReturnKind $returnKind,
        public array $bodyProperties,
        public bool $hasUploadedFile,
        public array $pathParameters,
        public bool $looksUpByRouteParameter,
        public HttpMethod $documentedMethod,
        public bool $usesMethodSpoofing,
        public array $warnings = [],
        public ?CallbackSource $callback = null,
    ) {
    }

    public function hasRequestBody(): bool
    {
        return $this->endpoint->wantsRequestBody() && $this->bodyProperties !== [];
    }

    /**
     * Whether the method body was written inline rather than delegated to an Action.
     */
    public function isCallback(): bool
    {
        return $this->callback !== null;
    }

    public function mediaType(): string
    {
        return $this->endpoint->getMediaType()
            ?? ($this->hasUploadedFile ? 'multipart/form-data' : 'application/json');
    }
}
