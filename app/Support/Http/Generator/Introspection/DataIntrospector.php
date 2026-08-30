<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\VO\FileValue;
use Illuminate\Http\UploadedFile;
use OpenApi\Attributes as OA;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * Splits a Data object into the properties that arrive in the request body and the
 * ones the HTTP layer injects.
 */
final class DataIntrospector
{
    /**
     * Constructor parameters not covered by a filler - i.e. what the client must send.
     *
     * @param  class-string  $dataClass
     * @param  list<string>  $filledProperties
     * @return list<ReflectionParameter>
     */
    public function bodyProperties(string $dataClass, array $filledProperties): array
    {
        $constructor = (new ReflectionClass($dataClass))->getConstructor();

        if ($constructor === null) {
            return [];
        }

        return array_values(array_filter(
            $constructor->getParameters(),
            static fn(ReflectionParameter $parameter): bool => !in_array($parameter->getName(), $filledProperties, true),
        ));
    }

    /**
     * Types that make a request body carry a file.
     *
     * A Data object may declare the raw upload or a domain value wrapping it
     * ({@see FileValue}); either way the endpoint speaks multipart.
     *
     * @var list<class-string>
     */
    private const FILE_TYPES = [
        UploadedFile::class,
        FileValue::class,
    ];

    /**
     * Whether the body carries a file, which forces `multipart/form-data`.
     *
     * @param  list<ReflectionParameter>  $properties
     */
    public function hasUploadedFile(array $properties): bool
    {
        foreach ($properties as $property) {
            foreach ($this->namedTypesOf($property) as $type) {
                if ($type->isBuiltin()) {
                    continue;
                }

                foreach (self::FILE_TYPES as $fileType) {
                    if (is_a($type->getName(), $fileType, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Body properties missing an `#[OA\Property]`, which would silently vanish from
     * the request schema.
     *
     * @param  list<ReflectionParameter>  $properties
     * @return list<string>
     */
    public function undocumentedProperties(array $properties): array
    {
        $undocumented = [];

        foreach ($properties as $property) {
            if ($property->getAttributes(OA\Property::class) === []) {
                $undocumented[] = $property->getName();
            }
        }

        return $undocumented;
    }

    /**
     * @return list<ReflectionNamedType>
     */
    private function namedTypesOf(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            return [$type];
        }

        if ($type instanceof ReflectionUnionType) {
            return array_values(array_filter(
                $type->getTypes(),
                static fn(mixed $inner): bool => $inner instanceof ReflectionNamedType,
            ));
        }

        return [];
    }
}
