<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\Php\ClassRef;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\ListLiteral;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use OpenApi\Attributes as OA;

/**
 * The request body of an endpoint.
 *
 * Body properties are never enumerated here: `new OA\Schema(ref: XData::class)` is
 * enough, because swagger-php resolves the FQCN against the Data object's own
 * `#[OA\Property]` attributes.
 */
final class RequestBodyFactory
{
    public function build(EndpointPlan $plan): ?Expr
    {
        if (!$plan->hasRequestBody()) {
            return null;
        }

        return new NewExpr(OA\RequestBody::class, [
            'required' => new Literal(true),
            'content' => new NewExpr(OA\MediaType::class, [
                'mediaType' => new Literal($plan->mediaType()),
                'schema' => $this->schema($plan),
            ]),
        ]);
    }

    private function schema(EndpointPlan $plan): Expr
    {
        $dataSchema = new NewExpr(OA\Schema::class, [
            'ref' => new ClassRef((string)$plan->dataClass),
        ]);

        if (!$plan->usesMethodSpoofing) {
            return $dataSchema;
        }

        $verb = strtoupper($plan->endpoint->method->value);

        return new NewExpr(OA\Schema::class, [
            'allOf' => new ListLiteral(
                $dataSchema,
                new NewExpr(OA\Schema::class, [
                    'required' => new Literal(['_method']),
                    'properties' => new ListLiteral(
                        new NewExpr(OA\Property::class, [
                            'property' => new Literal('_method'),
                            'type' => new Literal('string'),
                            'default' => new Literal($verb),
                            'enum' => new Literal([$verb]),
                        ]),
                    ),
                    'type' => new Literal('object'),
                ]),
            ),
        ]);
    }
}
