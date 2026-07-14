<?php
declare(strict_types=1);

namespace App\Support\Data\Filling;

use Attribute;
use Illuminate\Http\Request;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Fills a Data property from a route parameter.
 *
 * When the target property is a value object exposing a static
 * `fromRequestParameter(Request, string)` factory (e.g. a NumberIdentifier),
 * that factory is used so the raw route value is cast into the value object.
 * Otherwise the raw route value is returned as-is.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final readonly class FillFromRouteParameter implements DataPropertyFiller
{
    public function __construct(
        private string $property,
        private ?string $parameter = null,
    ) {
    }

    public function property(): string
    {
        return $this->property;
    }

    public function resolveValue(Request $request, ReflectionProperty $property): mixed
    {
        $parameter = $this->parameter ?? $this->property;

        $type = $property->getType();

        if ($type instanceof ReflectionNamedType) {
            $class = $type->getName();

            if (! $type->isBuiltin() && method_exists($class, 'fromRequestParameter')) {
                return $class::fromRequestParameter($request, $parameter);
            }
        }

        return $request->route($parameter);
    }
}
