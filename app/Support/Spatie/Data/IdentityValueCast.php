<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Core\AppIdentity;
use App\Models\User;
use App\Support\VO\IdentityValue;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class IdentityValueCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): IdentityValue|Uncastable
    {
        $identity = match (true) {
            $value instanceof AppIdentity => $value,
            $value instanceof User => AppIdentity::fromUser($value),
            default => null,
        };

        if ($identity === null) {
            return Uncastable::create();
        }

        /** @var class-string<IdentityValue> $type */
        $type = $property->type->type->findAcceptedTypeForBaseType(IdentityValue::class);

        return new $type($identity);
    }
}
