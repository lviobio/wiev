<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use App\Core\VO\FileValue;
use App\Core\VO\NumberIdentifier;
use App\Core\VO\StringValue;
use App\Core\VO\UuidIdentifier;
use OpenApi\Annotations as OA;

/**
 * The OpenAPI scalar shape a project value object already carries in its base class.
 *
 * A Data property typed `PostIdentifier $id` is, as far as the wire format goes, just
 * a number - the VO only exists so the domain never passes a bare int around. Writing
 * `#[OA\Property(type: 'integer')]` on every such property restates something the base
 * class ({@see NumberIdentifier}) already says once. This class is that lookup, used by
 * {@see SpatieDataTypeResolver} for request bodies and by
 * {@see \App\Support\Http\Generator\OpenApi\PathParametersFactory} for route parameters,
 * so both agree on what a given VO looks like without either hardcoding it.
 *
 * Unknown classes (nested `Data` objects, enums, plain models) return `null` - callers
 * fall back to whatever they'd have done without this lookup at all.
 */
final readonly class ScalarVoType
{
    public function __construct(
        public string $type,
        public ?string $format = null,
    ) {
    }

    /**
     * @param  class-string  $class
     */
    public static function for(string $class): ?self
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return null;
        }

        return match (true) {
            is_subclass_of($class, NumberIdentifier::class) => new self('integer'),
            is_subclass_of($class, UuidIdentifier::class) => new self('string', 'uuid'),
            is_subclass_of($class, FileValue::class) => new self('string', 'binary'),
            is_a($class, StringValue::class, true) => new self('string'),
            default => null,
        };
    }

    public function applyTo(OA\Schema $schema): OA\Schema
    {
        $schema->type = $this->type;

        if ($this->format !== null) {
            $schema->format = $this->format;
        }

        return $schema;
    }
}
