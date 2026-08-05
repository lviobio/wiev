<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use App\Support\Http\Generator\Introspection\ActionIntrospector;
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
    ) {
    }

    public function plan(Endpoint $endpoint): EndpointPlan
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

        // PHP does not parse multipart bodies on PUT/PATCH, so uploads have to arrive
        // as a POST carrying `_method`. The route keeps the real verb; the docs follow
        // what a client actually sends.
        $needsSpoofing = $endpoint->method->needsMethodSpoofingForUploads()
            && $hasUploadedFile
            && $endpoint->wantsRequestBody()
            && $bodyProperties !== [];

        $documentedMethod = $endpoint->getDocumentedAs()
            ?? ($needsSpoofing ? HttpMethod::Post : $endpoint->method);

        return new EndpointPlan(
            endpoint: $endpoint,
            dataClass: $dataClass,
            returnKind: $returnKind,
            bodyProperties: $bodyProperties,
            hasUploadedFile: $hasUploadedFile,
            routeParameters: $endpoint->routeParameters(),
            documentedMethod: $documentedMethod,
            usesMethodSpoofing: $needsSpoofing && $documentedMethod !== $endpoint->method,
            warnings: $warnings,
        );
    }
}
