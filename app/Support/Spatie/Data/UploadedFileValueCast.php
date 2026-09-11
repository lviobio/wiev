<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Core\VO\UploadedFileValue;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class UploadedFileValueCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): UploadedFileValue|Uncastable
    {
        if (!$value instanceof UploadedFile) {
            return Uncastable::create();
        }

        /** @var class-string<UploadedFileValue> $type */
        $type = $property->type->type->findAcceptedTypeForBaseType(UploadedFileValue::class);

        return $type::fromUploadedFile($value);
    }
}
