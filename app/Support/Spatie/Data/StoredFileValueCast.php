<?php
declare(strict_types=1);

namespace App\Support\Spatie\Data;

use App\Core\Upload\Models\TemporaryUpload;
use App\Core\VO\StoredFileValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Turns a temporary-upload identifier into the stored file it points at.
 *
 * Only a lookup - nothing is claimed here. The upload gets marked as used inside
 * the flush() transaction, at the moment it actually becomes media
 * ({@see \App\Support\Spatie\MediaLibrary\DeferredFileAdder}), so a request that
 * builds this value and then fails - or dies - leaves the upload untouched.
 *
 * The ownership check repeats what {@see \App\Core\Upload\Rules\TemporaryUploadRule}
 * verified on the HTTP path, because a Data object built outside a request
 * (`::from([...])`) skips validation and would otherwise accept anyone's upload.
 */
class StoredFileValueCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): StoredFileValue|Uncastable
    {
        if (!is_string($value) || !Str::isUuid($value)) {
            return Uncastable::create();
        }

        $upload = TemporaryUpload::query()
            ->usableBy(Auth::id())
            ->where('uuid', $value)
            ->first();

        if ($upload === null) {
            throw ValidationException::withMessages([
                $property->inputMappedName ?? $property->name => 'The file could not be found, has expired or has already been used.',
            ]);
        }

        /** @var class-string<StoredFileValue> $type */
        $type = $property->type->type->findAcceptedTypeForBaseType(StoredFileValue::class);

        return new $type(
            originalName: $upload->original_name,
            mimeType: $upload->mime_type ?? 'application/octet-stream',
            size: $upload->size,
            disk: $upload->disk,
            path: $upload->path,
        );
    }
}
