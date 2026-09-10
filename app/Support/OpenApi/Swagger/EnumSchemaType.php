<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use BackedEnum;
use OpenApi\Annotations as OA;
use ReflectionEnum;
use UnitEnum;

/**
 * The OpenAPI shape of a PHP enum: `type` from its backing type (`string` for a pure,
 * unbacked enum, which has no other value to put in the wire format than its case name),
 * `enum` from its cases.
 *
 * swagger-php's own {@see \OpenApi\Type\TypeInfoTypeResolver} has no opinion on enums at
 * all: an enum-typed property falls into its generic `ObjectType` branch, which sets
 * `type` to the enum's FQCN and then fails to turn that into a `$ref` (nothing gives a
 * bare enum its own `#[OA\Schema]`) - the property's `type` is left `Generator::UNDEFINED`.
 * This is the fix, consulted by {@see SpatieDataTypeResolver} before anything else gets
 * a chance to.
 */
final readonly class EnumSchemaType
{
    private function __construct(
        public string $type,
        public array $cases,
    ) {
    }

    /**
     * @param  class-string  $class
     */
    public static function for(string $class): ?self
    {
        if (!enum_exists($class)) {
            return null;
        }

        $reflection = new ReflectionEnum($class);

        if (!$reflection->isBacked()) {
            return new self('string', array_map(static fn (UnitEnum $case): string => $case->name, $class::cases()));
        }

        $type = $reflection->getBackingType()?->getName() === 'int' ? 'integer' : 'string';

        return new self($type, array_map(static fn (BackedEnum $case): int|string => $case->value, $class::cases()));
    }

    public function applyTo(OA\Schema $schema): OA\Schema
    {
        $schema->type = $this->type;
        $schema->enum = $this->cases;

        return $schema;
    }
}
