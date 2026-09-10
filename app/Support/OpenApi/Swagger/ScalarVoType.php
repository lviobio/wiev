<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use App\Core\VO\FileValue;
use App\Core\VO\HasValidationRules;
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
 * A {@see StringValue} that also declares {@see HasValidationRules} - `PostTitle`'s
 * `['string', 'min:3', 'max:255']` - gets `minLength`/`maxLength` from the same `min:`/
 * `max:` rules {@see \App\Support\Spatie\Data\ValueRuleInferrer} already feeds Laravel's
 * validator: the shape of the value is one fact, not one to state for validation and
 * another for documentation.
 *
 * Unknown classes (nested `Data` objects, enums, plain models) return `null` - callers
 * fall back to whatever they'd have done without this lookup at all.
 */
final readonly class ScalarVoType
{
    public function __construct(
        public string $type,
        public ?string $format = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
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
            is_a($class, StringValue::class, true) => self::string($class),
            default => null,
        };
    }

    /**
     * @param  class-string  $class
     */
    private static function string(string $class): self
    {
        if (!is_a($class, HasValidationRules::class, true)) {
            return new self('string');
        }

        [$minLength, $maxLength] = self::lengthConstraints($class::rules());

        return new self('string', minLength: $minLength, maxLength: $maxLength);
    }

    /**
     * Only `min:`/`max:` are read - the rest of a value's rules (`email`, a regex, ...)
     * don't have a lossless OpenAPI equivalent, and a wrong guess is worse than none.
     *
     * @param  list<mixed>  $rules
     * @return array{0: ?int, 1: ?int}
     */
    private static function lengthConstraints(array $rules): array
    {
        $minLength = $maxLength = null;

        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            if (preg_match('/^min:(\d+)$/', $rule, $matches) === 1) {
                $minLength = (int)$matches[1];
            } elseif (preg_match('/^max:(\d+)$/', $rule, $matches) === 1) {
                $maxLength = (int)$matches[1];
            }
        }

        return [$minLength, $maxLength];
    }

    public function applyTo(OA\Schema $schema): OA\Schema
    {
        $schema->type = $this->type;

        if ($this->format !== null) {
            $schema->format = $this->format;
        }

        if ($this->minLength !== null) {
            $schema->minLength = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $schema->maxLength = $this->maxLength;
        }

        return $schema;
    }
}
