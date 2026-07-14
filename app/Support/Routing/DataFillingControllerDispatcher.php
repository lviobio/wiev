<?php
declare(strict_types=1);

namespace App\Support\Routing;

use App\Support\Data\Filling\DataPropertyFiller;
use Illuminate\Http\Request;
use Illuminate\Routing\ControllerDispatcher;
use LogicException;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Spatie\LaravelData\Contracts\BaseData;

/**
 * Controller dispatcher that builds Spatie Data parameters from
 * {@see DataPropertyFiller} attributes declared on the controller method.
 *
 * This lets a controller keep the Data object HTTP-agnostic while still
 * declaring, at the HTTP boundary, how each property is populated - instead
 * of leaking `#[FromRouteParameter]` / `#[FromAuthenticatedUser]` into the
 * Data object itself.
 */
class DataFillingControllerDispatcher extends ControllerDispatcher
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters, $skippableValue)
    {
        $fillers = $parameter->getAttributes(DataPropertyFiller::class, ReflectionAttribute::IS_INSTANCEOF);

        if ($fillers !== []) {
            return $this->fillData($parameter, $fillers);
        }

        return parent::transformDependency($parameter, $parameters, $skippableValue);
    }

    /**
     * @param  array<int, ReflectionAttribute<DataPropertyFiller>>  $fillers
     */
    private function fillData(ReflectionParameter $parameter, array $fillers): BaseData
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin() || ! is_subclass_of($type->getName(), BaseData::class)) {
            throw new LogicException(sprintf(
                'Parameter [$%s] uses data-filling attributes but is not typed as a Spatie Data object.',
                $parameter->getName(),
            ));
        }

        /** @var class-string<BaseData> $dataClass */
        $dataClass = $type->getName();

        /** @var Request $request */
        $request = $this->container->make('request');

        $payload = [];

        foreach ($fillers as $attribute) {
            $filler = $attribute->newInstance();
            $property = new ReflectionProperty($dataClass, $filler->property());

            $payload[$filler->property()] = $filler->resolveValue($request, $property);
        }

        return $dataClass::from($payload);
    }
}
