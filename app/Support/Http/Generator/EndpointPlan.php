<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

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
     * @param  list<string>  $routeParameters
     * @param  list<string>  $warnings
     */
    public function __construct(
        public Endpoint $endpoint,
        public ?string $dataClass,
        public ReturnKind $returnKind,
        public array $bodyProperties,
        public bool $hasUploadedFile,
        public array $routeParameters,
        public HttpMethod $documentedMethod,
        public bool $usesMethodSpoofing,
        public array $warnings = [],
    ) {
    }

    public function hasRequestBody(): bool
    {
        return $this->endpoint->wantsRequestBody() && $this->bodyProperties !== [];
    }

    public function mediaType(): string
    {
        return $this->endpoint->getMediaType()
            ?? ($this->hasUploadedFile ? 'multipart/form-data' : 'application/json');
    }
}
