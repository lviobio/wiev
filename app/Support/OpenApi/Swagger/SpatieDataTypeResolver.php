<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Type\TypeInfoTypeResolver;
use Spatie\LaravelData\Optional;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Teaches swagger-php's default type resolver three things it doesn't know about a
 * Spatie\LaravelData property, so a bare `#[OA\Property]` is enough on it:
 *
 * - {@see Optional} marks "this key may be missing from the payload", not a real type.
 *   Left alone it survives into the union like any other member, and the resolver tries
 *   to $ref a class that carries no schema of its own - see {@see withoutOptional()}.
 * - The project's own value objects say what they wrap in their base class already
 *   ({@see ScalarVoType}), so `PostIdentifier $id` resolves to `integer` without a
 *   property having to repeat it.
 * - A PHP enum has no schema of its own to `$ref` either - {@see EnumSchemaType} reads
 *   its cases directly instead of leaving `type` undefined.
 *
 * Everything else - collections, nested Data objects that do have a schema - is left to
 * the parent; this class only intercepts the cases above.
 */
final class SpatieDataTypeResolver extends TypeInfoTypeResolver
{
    protected function setSchemaType(OA\Schema $schema, Type $type, Analysis $analysis, string $sourceClass = OA\Schema::class): OA\Schema
    {
        if ($type instanceof ObjectType) {
            $class = $type->getClassName();

            $enumType = EnumSchemaType::for($class);

            if ($enumType !== null) {
                return $enumType->applyTo($schema);
            }

            $voType = ScalarVoType::for($class);

            if ($voType !== null) {
                return $voType->applyTo($schema);
            }
        }

        if ($type instanceof UnionType) {
            $reduced = $this->withoutOptional($type);

            if ($reduced !== $type) {
                return $this->setSchemaType($schema, $reduced, $analysis, $sourceClass);
            }
        }

        return parent::setSchemaType($schema, $type, $analysis, $sourceClass);
    }

    /**
     * Drops `Optional` from a union. A `PostCover|null|Optional` property arrives here
     * as `UnionType(PostCover, Optional)` - PHP itself already peeled the `|null` off
     * into a wrapping `NullableType`, unwrapped by the resolver before this is called.
     *
     * Removing `Optional` collapses `UnionType(PostCover, Optional)` down to the plain
     * `PostCover` type, at which point the ObjectType branch above resolves it as usual.
     * Returns the input type unchanged when there was no `Optional` to drop.
     */
    private function withoutOptional(UnionType $type): Type
    {
        $members = $type->getTypes();

        $kept = array_values(array_filter(
            $members,
            static fn (Type $member): bool => !($member instanceof ObjectType && $member->getClassName() === Optional::class),
        ));

        if ($kept === $members || $kept === []) {
            return $type;
        }

        return count($kept) === 1 ? $kept[0] : Type::union(...$kept);
    }
}
