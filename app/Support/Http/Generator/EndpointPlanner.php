<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use App\Support\Http\Generator\Introspection\ActionIntrospector;
use App\Support\Http\Generator\Introspection\CallbackIntrospector;
use App\Support\Http\Generator\Introspection\DataIntrospector;
use App\Support\Http\Generator\Introspection\ReturnKind;

/**
 * Resolves a declared endpoint into an {@see EndpointPlan}.
 */
final readonly class EndpointPlanner
{
    public function __construct(
        private ActionIntrospector $actions = new ActionIntrospector(),
        private DataIntrospector $data = new DataIntrospector(),
        private CallbackIntrospector $callbacks = new CallbackIntrospector(),
    ) {
    }

    public function plan(Endpoint $endpoint, string $routeParameter = ''): EndpointPlan
    {
        $warnings = [];
        $dataClass = null;
        $returnKind = $endpoint->queryClass !== null ? ReturnKind::Collection : ReturnKind::Other;

        if ($endpoint->actionClass !== null) {
            $dataClass = $this->actions->dataClass($endpoint->actionClass);
            $returnKind = $this->actions->returnKind($endpoint->actionClass);
        }

        $filled = array_map(
            static fn($filler): string => $filler->property(),
            $endpoint->fillers(),
        );

        $bodyProperties = $dataClass === null
            ? []
            : $this->data->bodyProperties($dataClass, $filled);

        foreach ($this->data->undocumentedProperties($bodyProperties) as $property) {
            $warnings[] = sprintf(
                '%s::$%s is sent in the request body but carries no #[OA\Property]; it will be missing from the schema.',
                class_basename((string)$dataClass),
                $property,
            );
        }

        $hasUploadedFile = $this->data->hasUploadedFile($bodyProperties);

        // The real verb is what gets documented. Laravel's `_method` spoofing exists for
        // browser forms, which cannot send PUT at all; a proper API client just sends the
        // PUT, so documenting the route as POST would misdescribe it for everyone else.
        // Opting in with ->documentAs() is still possible where the browser is the client.
        $documentedMethod = $endpoint->getDocumentedAs() ?? $endpoint->method;

        $usesMethodSpoofing = $documentedMethod !== $endpoint->method
            && $endpoint->method->supportsMethodSpoofing()
            && $documentedMethod === HttpMethod::Post;

        return new EndpointPlan(
            endpoint: $endpoint,
            dataClass: $dataClass,
            returnKind: $returnKind,
            bodyProperties: $bodyProperties,
            hasUploadedFile: $hasUploadedFile,
            // Documented from the URI rather than from the fillers: a callback endpoint has
            // no fillers at all, yet its `{post}` still has to appear in the spec.
            pathParameters: $this->pathParametersOf($endpoint, $routeParameter),
            looksUpByRouteParameter: $endpoint->callback === null && $endpoint->routeParameters() !== [],
            documentedMethod: $documentedMethod,
            usesMethodSpoofing: $usesMethodSpoofing,
            warnings: $warnings,
            callback: $endpoint->callback === null
                ? null
                : $this->callbacks->describe($endpoint->callback),
        );
    }

    /**
     * @return list<string>
     */
    private function pathParametersOf(Endpoint $endpoint, string $routeParameter): array
    {
        preg_match_all('/\{(\w+)\??}/', $endpoint->resolvedUri($routeParameter), $matches);

        return $matches[1];
    }
}
