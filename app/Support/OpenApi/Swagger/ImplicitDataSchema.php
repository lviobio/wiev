<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use App\Http\Controllers\Controller;
use App\Support\Data\Filling\DataPropertyFiller;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use Spatie\LaravelData\Data;

/**
 * A `Spatie\LaravelData\Data` class used as a controller method's request body doesn't
 * have to say so itself: `#[OA\Schema]` on the class and `#[OA\Property]` on its
 * properties become optional, the way {@see RequiredFromNullability} already made
 * `required` optional. Where they're missing, this synthesizes the same annotations
 * swagger-php would have built from real attributes - reusing the real `Context` the
 * initial scan already created for the class and each of its properties, so every later
 * processor (type resolution, `$ref` lookup, component merging) can't tell the
 * difference.
 *
 * A property covered by a {@see DataPropertyFiller} attribute on the controller
 * parameter - `#[FillFromRouteParameter('id', 'post')]` and friends - is left out
 * entirely: it never arrives in the request body, so documenting it as part of one
 * would be describing a field the client can't actually send.
 *
 * A class that already carries `#[OA\Schema]` is left untouched, in full - that stays
 * the escape hatch for a Data object whose documentation needs something this can't
 * infer. So does an individual `#[OA\Property]`: a property that already has one keeps
 * it, and only the properties without one get a synthesized bare `#[OA\Property]`.
 */
final class ImplicitDataSchema
{
    public function __invoke(Analysis $analysis): void
    {
        foreach ($this->usages($analysis) as $class => $filledProperties) {
            $this->synthesize($analysis, $class, $filledProperties);
        }
    }

    /**
     * Every `Data` subclass that appears as a controller method parameter, together
     * with the property names a `DataPropertyFiller` attribute on that parameter
     * already covers.
     *
     * Scoped to {@see Controller} subclasses specifically, not just any class with a
     * `Data`-typed parameter: an Action's own `__invoke(XData $data)` would otherwise
     * match too, and unlike the controller method that dispatches to it, it never
     * carries filler attributes - documenting off of it would wrongly turn every
     * server-filled property back into a client-supplied one.
     *
     * @return array<class-string<Data>, list<string>>
     */
    private function usages(Analysis $analysis): array
    {
        $usages = [];

        foreach (array_keys($analysis->classes) as $class) {
            $class = ltrim($class, '\\');

            if (!class_exists($class) || !is_subclass_of($class, Controller::class)) {
                continue;
            }

            foreach (new ReflectionClass($class)->getMethods() as $method) {
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();

                    if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || !is_subclass_of($type->getName(), Data::class)) {
                        continue;
                    }

                    $dataClass = $type->getName();
                    $usages[$dataClass] ??= [];

                    foreach ($parameter->getAttributes(DataPropertyFiller::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                        $usages[$dataClass][] = $attribute->newInstance()->property();
                    }
                }
            }
        }

        return $usages;
    }

    /**
     * @param  class-string<Data>  $class
     * @param  list<string>  $filledProperties
     */
    private function synthesize(Analysis $analysis, string $class, array $filledProperties): void
    {
        if ($analysis->getAnnotationForSource($class) !== null) {
            return; // a real #[OA\Schema] already documents this class - leave it alone
        }

        $definition = $analysis->classes['\\'.ltrim($class, '\\')] ?? null;

        if ($definition === null) {
            return;
        }

        $context = $definition['context'];

        $analysis->addAnnotation(new OA\Schema(['_context' => $context]), $context);

        foreach (new ReflectionClass($class)->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            if (in_array($name, $filledProperties, true)) {
                continue; // filled server-side, never part of the request body
            }

            $propertyContext = $definition['properties'][$name] ?? null;

            if ($propertyContext === null || $this->hasOwnProperty($propertyContext)) {
                continue;
            }

            $analysis->addAnnotation(new OA\Property(['_context' => $propertyContext]), $propertyContext);
        }
    }

    private function hasOwnProperty(Context $context): bool
    {
        return array_any($context->annotations ?? [], fn($annotation) => $annotation instanceof OA\Property);
    }
}
