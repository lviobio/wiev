<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\Php\AttributeExpr;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\ListLiteral;
use App\Support\Http\Generator\Php\Literal;

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

        $parameters = [
            ...$this->pathParameters->build($plan),
            ...$this->listingParameters($definition, $plan),
            ...$endpoint->getExtraParameters(),
        ];

        if ($parameters !== []) {
            $arguments['parameters'] = ListLiteral::of($parameters);
        }

        $arguments['responses'] = ListLiteral::of([
            ...$this->responses->build($definition, $plan),
            ...$endpoint->getExtraResponses(),
        ]);

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
    private function listingParameters(ControllerDefinition $definition, EndpointPlan $plan): array
    {
        if ($plan->endpoint->queryClass === null) {
            return [];
        }

        $parameters = $this->queryParameters->build(
            $this->queries->describe($plan->endpoint->queryClass),
            $definition->naming(),
        );

        $this->warnings = [...$this->warnings, ...$this->queryParameters->warnings()];

        return $parameters;
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
