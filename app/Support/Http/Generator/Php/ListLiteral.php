<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * A `[...]` array of expressions.
 */
final readonly class ListLiteral implements Expr
{
    /** @var list<Expr> */
    private array $items;

    public function __construct(Expr ...$items)
    {
        $this->items = array_values($items);
    }

    /**
     * @param  list<Expr>  $items
     */
    public static function of(array $items): self
    {
        return new self(...$items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function render(ImportCollector $imports, int $indent): string
    {
        $parts = array_map(
            static fn(Expr $item): callable => static fn(int $inner): string => $item->render($imports, $inner),
            $this->items,
        );

        return Printer::joinParts($parts, '[', ']', $indent);
    }
}
