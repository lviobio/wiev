<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\Http\Generator\GeneratorException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;
use ReflectionNamedType;
use Spatie\LaravelData\Contracts\BaseData;

/**
 * Reads an Action's `__invoke()` signature.
 *
 * This is what lets a declaration name only the Action: its first parameter identifies
 * the Data object, and its return type identifies the response.
 */
final class ActionIntrospector
{
    /**
     * @param  class-string  $actionClass
     * @return class-string<BaseData>
     */
    public function dataClass(string $actionClass): string
    {
        $parameters = $this->invoke($actionClass)->getParameters();
        $type = $parameters[0]?->getType() ?? null;

        if (!$type instanceof ReflectionNamedType
            || $type->isBuiltin()
            || !is_subclass_of($type->getName(), BaseData::class)) {
            throw GeneratorException::actionHasNoDataParameter($actionClass);
        }

        /** @var class-string<BaseData> */
        return $type->getName();
    }

    /**
     * @param  class-string  $actionClass
     */
    public function returnKind(string $actionClass): ReturnKind
    {
        $type = $this->invoke($actionClass)->getReturnType();

        if (!$type instanceof ReflectionNamedType) {
            return ReturnKind::Other;
        }

        if ($type->isBuiltin()) {
            return $type->getName() === 'void' ? ReturnKind::Void : ReturnKind::Other;
        }

        $name = $type->getName();

        if (is_a($name, EloquentCollection::class, true)) {
            return ReturnKind::Collection;
        }

        if (is_a($name, Model::class, true)) {
            return ReturnKind::Model;
        }

        return ReturnKind::Other;
    }

    /**
     * @param  class-string  $actionClass
     */
    private function invoke(string $actionClass): ReflectionMethod
    {
        if (!method_exists($actionClass, '__invoke')) {
            throw GeneratorException::actionIsNotInvokable($actionClass);
        }

        return new ReflectionMethod($actionClass, '__invoke');
    }
}
