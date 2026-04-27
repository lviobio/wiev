<?php
declare(strict_types=1);

namespace App\Support\VO;

use Illuminate\Http\Request;

abstract readonly class NumberIdentifier
{
    public function __construct(public int $value)
    {
    }

    public static function make(int $value): static
    {
        return new static($value);
    }

    public static function fromRequestParameter(Request $request, string $parameter): static
    {
        return new static((int)$request->route($parameter));
    }
}
