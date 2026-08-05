<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\Data\Filling\DataPropertyFiller;
use App\Support\Http\Generator\Php\AttributeExpr;
use App\Support\Http\Generator\Php\Literal;
use ReflectionClass;
use ReflectionParameter;

/**
 * Turns a filler instance from the declaration back into the attribute that produced it.
 *
 * The generated controller re-declares the fillers as parameter attributes so the
 * runtime path stays exactly as it is today: the dispatcher reads them off the method.
 */
final class FillerIntrospector
{
    public function toAttributeExpr(DataPropertyFiller $filler): AttributeExpr
    {
        $reflection = new ReflectionClass($filler);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new AttributeExpr($reflection->getName());
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = [
                'value' => $this->valueOf($reflection, $filler, $parameter),
                'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                'optional' => $parameter->isDefaultValueAvailable(),
            ];
        }

        // Drop trailing arguments that only restate their default, so the emitted
        // attribute reads the way it was written in the declaration.
        while ($arguments !== []) {
            $last = $arguments[array_key_last($arguments)];

            if (!$last['optional'] || $last['value'] !== $last['default']) {
                break;
            }

            array_pop($arguments);
        }

        return new AttributeExpr(
            $reflection->getName(),
            array_map(static fn(array $argument): Literal => new Literal($argument['value']), $arguments),
        );
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     */
    private function valueOf(ReflectionClass $reflection, DataPropertyFiller $filler, ReflectionParameter $parameter): mixed
    {
        $name = $parameter->getName();

        if (!$reflection->hasProperty($name)) {
            return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
        }

        return $reflection->getProperty($name)->getValue($filler);
    }
}
