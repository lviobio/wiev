<?php
declare(strict_types=1);

namespace App\Support\Data\Filling;

use App\Support\Routing\ControllerDispatchException;
use Illuminate\Http\Request;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Spatie\LaravelData\Contracts\BaseData;

/**
 * Builds a Spatie Data parameter from the {@see DataPropertyFiller} attributes declared
 * next to it on a controller method.
 *
 * Kept apart from the dispatcher so the dispatcher stays a coordinator: this class knows
 * about Data objects and nothing about routing.
 */
final readonly class DataParameterFiller
{
    /**
     * Whether this parameter declares how it should be filled.
     */
    public function handles(ReflectionParameter $parameter): bool
    {
        return $this->fillersOf($parameter) !== [];
    }

    public function fill(ReflectionParameter $parameter, Request $request): BaseData
    {
        $dataClass = $this->dataClassOf($parameter);

        $payload = [];

        foreach ($this->fillersOf($parameter) as $attribute) {
            $filler = $attribute->newInstance();
            $property = new ReflectionProperty($dataClass, $filler->property());

            $payload[$filler->property()] = $filler->resolveValue($request, $property);
        }

        // Merge the request body (drives validation of body fields) with the filler
        // payload (the injected, non-body properties - auth user, route params).
        // Injected values win on key collision. Validating the merged payload keeps
        // the injected properties present, so their `required` rules pass instead of
        // failing as "missing from the request".
        return $dataClass::validateAndCreate(array_merge($request->all(), $payload));
    }

    /**
     * @return array<int, ReflectionAttribute<DataPropertyFiller>>
     */
    private function fillersOf(ReflectionParameter $parameter): array
    {
        return $parameter->getAttributes(DataPropertyFiller::class, ReflectionAttribute::IS_INSTANCEOF);
    }

    /**
     * @return class-string<BaseData>
     */
    private function dataClassOf(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType
            || $type->isBuiltin()
            || !is_subclass_of($type->getName(), BaseData::class)) {
            throw ControllerDispatchException::parameterIsNotData($parameter->getName());
        }

        /** @var class-string<BaseData> */
        return $type->getName();
    }
}
