<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Support\VO\StringValue;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Строка из запроса — в конкретный StringValue, объявленный в типе свойства.
 *
 * Инвариант проверяет сам VO, поэтому невалидное значение здесь превратится
 * в DomainException. Чтобы клиент получал 422, а не 500, Data-объект должен
 * отдавать в правилах форму значения — см. ValidatedStringValue::rules().
 */
class StringValueCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): StringValue|Uncastable
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return Uncastable::create();
        }

        /** @var class-string<StringValue> $type */
        $type = $property->type->type->findAcceptedTypeForBaseType(StringValue::class);

        return new $type((string) $value);
    }
}
