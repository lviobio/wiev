<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use App\Support\OpenApi\Swagger\ScalarVoType;
use OpenApi\Attributes as OA;
use ReflectionClass;
use ReflectionNamedType;

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
            fn(string $parameter): Expr => new NewExpr(OA\PathParameter::class, [
                'name' => new Literal($parameter),
                'required' => new Literal(true),
                'schema' => new NewExpr(OA\Schema::class, ['type' => new Literal($this->schemaType($plan, $parameter))]),
            ]),
            $plan->pathParameters,
        );
    }

    /**
     * `{post}` is a string in the URI, but the app addresses posts by
     * {@see \App\Core\VO\NumberIdentifier}-shaped value objects, i.e. `integer` - the same
     * lookup {@see \App\Support\OpenApi\Swagger\SpatieDataTypeResolver} uses for request
     * bodies. Anything the filler chain doesn't resolve to a known VO stays `string`,
     * same as before this lookup existed.
     */
    private function schemaType(EndpointPlan $plan, string $parameter): string
    {
        if ($plan->dataClass === null) {
            return 'string';
        }

        $property = $this->propertyFilling($plan, $parameter);
        $type = $property === null ? null : $this->propertyType($plan->dataClass, $property);

        return $type === null ? 'string' : (ScalarVoType::for($type)?->type ?? 'string');
    }

    /**
     * Name of the Data property this route parameter fills, if any.
     */
    private function propertyFilling(EndpointPlan $plan, string $parameter): ?string
    {
        foreach ($plan->endpoint->fillers() as $filler) {
            if ($filler instanceof FillFromRouteParameter && $filler->parameter() === $parameter) {
                return $filler->property();
            }
        }

        return null;
    }

    /**
     * @param  class-string  $dataClass
     */
    private function propertyType(string $dataClass, string $property): ?string
    {
        foreach ((new ReflectionClass($dataClass))->getConstructor()?->getParameters() ?? [] as $parameter) {
            if ($parameter->getName() === $property && $parameter->getType() instanceof ReflectionNamedType) {
                return $parameter->getType()->getName();
            }
        }

        return null;
    }
}
