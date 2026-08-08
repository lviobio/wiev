<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * A `new Foo(name: value, ...)` call.
 *
 * String keys become named arguments, integer keys stay positional.
 */
final readonly class NewExpr implements Expr
{
    /**
     * @param  array<array-key, Expr>  $arguments
     */
    public function __construct(
        private string $fqcn,
        private array $arguments = [],
    ) {
    }

    public function render(ImportCollector $imports, int $indent, int $reserved = 0): string
    {
        $open = 'new ' . $imports->reference($this->fqcn) . '(';

        return Printer::joinParts(
            Arguments::parts($this->arguments, $imports),
            $open,
            ')',
            $indent,
            $reserved,
        );
    }
}
