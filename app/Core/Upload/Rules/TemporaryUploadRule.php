<?php
declare(strict_types=1);

namespace App\Core\Upload\Rules;

use App\Core\Upload\Models\TemporaryUpload;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Checks that a value references a temporary upload the authenticated user can still
 * claim, and that the upload fits the constraints the receiving value declares.
 *
 * Fluent, the way Laravel's own `File` rule is, so a value's rules read as a
 * description of the file rather than as plumbing:
 *
 *     TemporaryUploadRule::make()->image()->maxSize(2 * 1024 * 1024)
 *
 * Constraints are checked against the metadata recorded when the file was staged
 * ({@see \App\Core\Upload\Actions\StoreUpload\StoreUploadAction}) - the file itself may
 * live on a remote disk, so re-reading its bytes here isn't an option. That metadata
 * is server-derived (mime sniffed from content, size measured), not client-declared.
 *
 * A pre-check only: {@see \App\Support\Spatie\Data\StoredFileValueCast} does the same
 * lookup again (plus the atomic claim) when the value is built, because laravel-data's
 * `OnlyRequests` validation strategy means rules never run for a Data object built
 * outside an HTTP request. This is what turns a bad reference into a 422 instead of
 * a failed cast.
 */
final class TemporaryUploadRule implements ValidationRule
{
    /** @var list<string> mime types, `image/*`-style wildcards allowed */
    private array $mimeTypes = [];

    private ?int $maxSizeBytes = null;

    public static function make(): self
    {
        return new self();
    }

    public function image(): self
    {
        return $this->mimeTypes('image/*');
    }

    public function mimeTypes(string ...$mimeTypes): self
    {
        $this->mimeTypes = [...$this->mimeTypes, ...array_values($mimeTypes)];

        return $this;
    }

    public function maxSize(int $bytes): self
    {
        $this->maxSizeBytes = $bytes;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !Str::isUuid($value)) {
            $fail('The :attribute must be a valid uploaded file reference.');

            return;
        }

        $upload = TemporaryUpload::query()
            ->usableBy(Auth::id())
            ->where('uuid', $value)
            ->first();

        if ($upload === null) {
            $fail('The :attribute could not be found, has expired or has already been used.');

            return;
        }

        if ($this->mimeTypes !== [] && !$this->matchesMimeTypes((string) $upload->mime_type)) {
            $fail($this->mimeTypes === ['image/*']
                ? 'The :attribute must be an image.'
                : 'The :attribute must be a file of type: ' . implode(', ', $this->mimeTypes) . '.');

            return;
        }

        if ($this->maxSizeBytes !== null && $upload->size > $this->maxSizeBytes) {
            $fail('The :attribute must not be larger than ' . $this->maxSizeBytes . ' bytes.');
        }
    }

    private function matchesMimeTypes(string $mimeType): bool
    {
        foreach ($this->mimeTypes as $allowed) {
            if (Str::is($allowed, $mimeType)) {
                return true;
            }
        }

        return false;
    }
}
