<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Support\VO\NumberIdentifier;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class NumberIdentifierCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): NumberIdentifier|Uncastable
    {
        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            /** @var class-string<NumberIdentifier> $type */
            $type = $property->type->type->findAcceptedTypeForBaseType(NumberIdentifier::class);

            return new $type((int) $value);
        }

        return Uncastable::create();
    }
}
