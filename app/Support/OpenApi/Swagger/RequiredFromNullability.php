<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Spatie\LaravelData\Optional;

/**
 * Fills in a class-level `#[OA\Schema]`'s `required` from the constructor it describes,
 * instead of a Data object restating by hand what its own property types already say:
 * `title: string` and `content: ?string` already mean "title is required, content isn't"
 * - {@see \App\Modules\Post\Actions\UpdatePost\UpdatePostData} used to say so a second
 * time via `#[OA\Schema(required: ['title'])]`.
 *
 * A documented property (one that made it into `$schema->properties`, i.e. carries its
 * own `#[OA\Property]`) is required unless its PHP type admits `null` or {@see Optional}
 * - exactly the two ways a Spatie\LaravelData property says "this key may be absent
 * from the request", the same vocabulary {@see SpatieDataTypeResolver} already reads.
 * A constructor default counts too: a parameter that doesn't have to be passed at all
 * isn't required either.
 *
 * Only runs where `required` was left at its default, so `->required(...)` written by
 * hand stays a working escape hatch for whatever this heuristic gets wrong.
 */
final class RequiredFromNullability
{
    public function __invoke(Analysis $analysis): void
    {
        /** @var OA\Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(OA\Schema::class, true);

        foreach ($schemas as $schema) {
            if (!Generator::isDefault($schema->required) || $schema->properties === Generator::UNDEFINED) {
                continue;
            }

            $class = $this->classOf($schema);

            if ($class === null) {
                continue;
            }

            $required = $this->requiredProperties($class, $schema->properties);

            if ($required !== []) {
                $schema->required = $required;
            }
        }
    }

    /**
     * @return class-string|null
     */
    private function classOf(OA\Schema $schema): ?string
    {
        $context = $schema->_context;

        if (!$context->is('class') || $context->class === null) {
            return null;
        }

        $class = ltrim(($context->namespace ? $context->namespace.'\\' : '').$context->class, '\\');

        return class_exists($class) ? $class : null;
    }

    /**
     * @param  class-string  $class
     * @param  OA\Property[]  $properties
     * @return list<string>
     */
    private function requiredProperties(string $class, array $properties): array
    {
        $parameters = [];

        foreach ((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        $required = [];

        foreach ($properties as $property) {
            $name = $property->property;

            if ($name === Generator::UNDEFINED || !isset($parameters[$name])) {
                continue;
            }

            if ($this->isRequired($parameters[$name])) {
                $required[] = $name;
            }
        }

        return $required;
    }

    private function isRequired(ReflectionParameter $parameter): bool
    {
        if ($parameter->isDefaultValueAvailable()) {
            return false;
        }

        $type = $parameter->getType();

        return $type !== null && !$type->allowsNull() && !$this->admitsOptional($type);
    }

    private function admitsOptional(ReflectionType $type): bool
    {
        if (!$type instanceof ReflectionUnionType) {
            return false;
        }

        foreach ($type->getTypes() as $member) {
            if ($member instanceof ReflectionNamedType && $member->getName() === Optional::class) {
                return true;
            }
        }

        return false;
    }
}
