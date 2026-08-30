<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Support\VO\FileValue;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class FileValueCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): FileValue|Uncastable
    {
        if (!$value instanceof UploadedFile) {
            return Uncastable::create();
        }

        /** @var class-string<FileValue> $type */
        $type = $property->type->type->findAcceptedTypeForBaseType(FileValue::class);

        return $type::fromUploadedFile($value);
    }
}
