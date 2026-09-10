<?php
declare(strict_types=1);

namespace App\Core\VO;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Идентификатор-uuid. Парный к {@see NumberIdentifier} для ресурсов,
 * которые не хочется адресовать перебираемым числом.
 */
abstract readonly class UuidIdentifier
{
    public function __construct(public string $value)
    {
    }

    public static function make(string $value): static
    {
        return new static($value);
    }

    public static function fromRequestParameter(Request $request, string $parameter): static
    {
        $value = $request->route($parameter);

        if (!is_string($value) || !Str::isUuid($value)) {
            throw RouteParameterUnresolvableException::make($parameter);
        }

        return new static($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
