<?php

declare(strict_types = 1);

namespace App\Support\Spatie\Data;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class SmartDateTimeInterfaceCast extends DateTimeInterfaceCast
{
    public function cast(
        DataProperty $property,
        mixed $value,
        array $properties,
        CreationContext $context,
    ): DateTimeInterface|Uncastable {
        if (is_numeric($value)) {
            /** @var class-string<DateTimeInterface> $type */
            $type = $this->type ?? $property->type->type->findAcceptedTypeForBaseType(DateTimeInterface::class);

            return match (true) {
                $type === CarbonImmutable::class, is_subclass_of(
                    $type,
                    CarbonImmutable::class,
                ) => CarbonImmutable::createFromTimestampMs((int) $value),
                $type === Carbon::class, is_subclass_of($type, Carbon::class) => Carbon::createFromTimestampMs(
                    (int) $value,
                ),
                default => $type::createFromFormat(
                    'U.u',
                    sprintf('%d.%06d', intdiv((int) $value, 1000), ((int) $value % 1000) * 1000),
                ),
            };
        }

        return parent::cast($property, $value, $properties, $context);
    }
}
