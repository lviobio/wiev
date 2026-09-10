<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\Php\AttributeExpr;
use App\Support\Http\Generator\Php\ClassRef;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\ListLiteral;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\MapLiteral;
use App\Support\OpenApi\Swagger\ListingQueryParameters;

/**
 * Assembles the single `#[OA\Get|Post|...]` attribute documenting an endpoint.
 */
final class OperationFactory
{
    /** @var list<string> */
    private array $warnings = [];

    public function __construct(
        private readonly QueryIntrospector $queries,
        private readonly PathParametersFactory $pathParameters = new PathParametersFactory(),
        private readonly QueryParametersFactory $queryParameters = new QueryParametersFactory(),
        private readonly RequestBodyFactory $requestBody = new RequestBodyFactory(),
        private readonly ResponsesFactory $responses = new ResponsesFactory(),
    ) {
    }

    public function build(ControllerDefinition $definition, EndpointPlan $plan): ?Expr
    {
        $endpoint = $plan->endpoint;

        if (!$endpoint->isDocumented()) {
            return null;
        }

        if ($endpoint->getOpenApiOverride() !== null) {
            return $endpoint->getOpenApiOverride();
        }

        $naming = $definition->naming();

        // Argument order mirrors how these attributes are written by hand in this
        // codebase; absent keys are simply skipped.
        $arguments = [
            'path' => new Literal($definition->pathFor($endpoint)),
            'operationId' => new Literal(
                $endpoint->getOperationId() ?? $naming->operationId($endpoint->kind, $endpoint->controllerMethod),
            ),
        ];

        $description = $endpoint->getDescription() ?? $this->spoofingDescription($plan);

        if ($description !== null) {
            $arguments['description'] = new Literal($description);
        }

        $arguments['summary'] = new Literal(
            $endpoint->getSummary() ?? $naming->summary($endpoint->kind, $endpoint->controllerMethod),
        );

        $security = $endpoint->getSecurity() ?? $definition->getSecurity();

        if ($security !== []) {
            $arguments['security'] = new Literal([array_fill_keys($security, [])]);
        }

        if ($endpoint->isDeprecated()) {
            $arguments['deprecated'] = new Literal(true);
        }

        $requestBody = $this->requestBody->build($plan);

        if ($requestBody !== null) {
            $arguments['requestBody'] = $requestBody;
        }

        $arguments['tags'] = new Literal($endpoint->getTags() ?? $definition->getTags());

        if ($endpoint->queryClass !== null) {
            // Discarded - kept only so an unrecognised filter still surfaces as a
            // `http:generate` warning at commit-review time, same as always. The
            // parameters themselves are never written here: ListingQueryParameters
            // expands `x` into them straight off the query class when the spec is
            // actually built, so a filter added later shows up without regenerating.
            $this->listingParameters($plan);
        }

        $parameters = [
            ...$this->pathParameters->build($plan),
            ...$endpoint->getExtraParameters(),
        ];

        if ($parameters !== []) {
            $arguments['parameters'] = ListLiteral::of($parameters);
        }

        $arguments['responses'] = ListLiteral::of([
            ...$endpoint->getResponsesOverride() ?? $this->responses->build($definition, $plan),
            ...$endpoint->getExtraResponses(),
        ]);

        if ($endpoint->queryClass !== null) {
            $arguments['x'] = $this->listingParametersMarker($endpoint);
        }

        return new AttributeExpr($plan->documentedMethod->attributeClass(), $arguments);
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return list<Expr>
     */
    private function listingParameters(EndpointPlan $plan): array
    {
        if ($plan->endpoint->queryClass === null) {
            return [];
        }

        $parameters = $this->queryParameters->build(
            $this->queries->describe($plan->endpoint->queryClass),
            $plan->endpoint->isCursorPaginated(),
        );

        $this->warnings = [...$this->warnings, ...$this->queryParameters->warnings()];

        return $parameters;
    }

    /**
     * Names the query class (and cursor mode) under a vendor extension, so
     * {@see ListingQueryParameters} can find and expand it at doc-build time.
     */
    private function listingParametersMarker(Endpoint $endpoint): Expr
    {
        $marker = [
            ListingQueryParameters::X_QUERY_PARAMS_REF => new ClassRef((string)$endpoint->queryClass),
        ];

        if ($endpoint->isCursorPaginated()) {
            $marker[ListingQueryParameters::X_QUERY_PARAMS_CURSOR] = new Literal(true);
        }

        return new MapLiteral($marker);
    }

    private function spoofingDescription(EndpointPlan $plan): ?string
    {
        if (!$plan->usesMethodSpoofing) {
            return null;
        }

        $realVerb = strtoupper($plan->endpoint->method->value);
        $documentedVerb = strtoupper($plan->documentedMethod->value);

        return "Send as {$documentedVerb} with a `_method={$realVerb}` field "
            . '(Laravel method spoofing) so the multipart body is parsed.';
    }
}
