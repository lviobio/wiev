<?php
declare(strict_types=1);

namespace App\Authorization;

use App\Enums\AuthAbilityEnum;
use Attribute;

/**
 * Declares the ability a controller method requires.
 *
 * ```php
 * #[CheckAuthAbility(AuthAbilityEnum::Access, Post::class)]
 * public function index(PostIndexQuery $query): AnonymousResourceCollection
 * ```
 *
 * The check itself runs in {@see \App\Support\Routing\ControllerAbilityAuthorizer},
 * before the method's parameters are resolved - a forbidden request should not get as
 * far as building and validating a Data object.
 *
 * Repeatable: several abilities on one method are all required.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class CheckAuthAbility
{
    /**
     * @param  class-string|null  $target  Model class the ability is scoped to, if any.
     */
    public function __construct(
        public AuthAbilityEnum|string $ability,
        public ?string $target = null,
    ) {
    }

    /**
     * The ability name as Bouncer stores it.
     */
    public function ability(): string
    {
        return $this->ability instanceof AuthAbilityEnum
            ? $this->ability->value
            : $this->ability;
    }

    /**
     * @return class-string|null
     */
    public function target(): ?string
    {
        return $this->target;
    }

    /**
     * Arguments for `Gate::authorize()`: an unscoped ability passes none.
     *
     * @return list<mixed>
     */
    public function gateArguments(): array
    {
        return $this->target === null ? [] : [$this->target];
    }
}
