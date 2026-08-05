<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * A `Foo::class` reference, importing `Foo` as a side effect.
 */
final readonly class ClassRef implements Expr
{
    public function __construct(private string $fqcn)
    {
    }

    public function render(ImportCollector $imports, int $indent): string
    {
        return $imports->reference($this->fqcn) . '::class';
    }
}
