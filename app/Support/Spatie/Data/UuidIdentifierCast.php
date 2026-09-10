<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Core\VO\UuidIdentifier;
use Illuminate\Support\Str;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class UuidIdentifierCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): UuidIdentifier|Uncastable
    {
        if (!is_string($value) || !Str::isUuid($value)) {
            return Uncastable::create();
        }

        /** @var class-string<UuidIdentifier> $type */
        $type = $property->type->type->findAcceptedTypeForBaseType(UuidIdentifier::class);

        return new $type($value);
    }
}
