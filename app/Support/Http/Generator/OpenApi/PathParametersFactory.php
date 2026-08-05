<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use OpenApi\Attributes as OA;

/**
 * Path parameters, taken from the endpoint's route-parameter fillers.
 */
final class PathParametersFactory
{
    /**
     * @return list<Expr>
     */
    public function build(EndpointPlan $plan): array
    {
        return array_map(
            static fn(string $parameter): Expr => new NewExpr(OA\PathParameter::class, [
                'name' => new Literal($parameter),
                'required' => new Literal(true),
                'schema' => new NewExpr(OA\Schema::class, ['type' => new Literal('string')]),
            ]),
            $plan->routeParameters,
        );
    }
}
