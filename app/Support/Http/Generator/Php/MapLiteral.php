<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * A `['key' => value]` array with string keys, preserving insertion order.
 */
final readonly class MapLiteral implements Expr
{
    /**
     * @param  array<string, Expr>  $entries
     */
    public function __construct(private array $entries)
    {
    }

    public function render(ImportCollector $imports, int $indent): string
    {
        $parts = [];

        foreach ($this->entries as $key => $value) {
            $prefix = (new Literal($key))->render($imports, $indent) . ' => ';

            $parts[] = static fn(int $inner): string => $prefix . $value->render($imports, $inner);
        }

        return Printer::joinParts($parts, '[', ']', $indent);
    }
}
