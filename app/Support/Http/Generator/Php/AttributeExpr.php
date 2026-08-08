<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * A `#[Foo(...)]` attribute.
 */
final readonly class AttributeExpr implements Expr
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
        $reference = $imports->reference($this->fqcn);

        if ($this->arguments === []) {
            return "#[{$reference}]";
        }

        return Printer::joinParts(
            Arguments::parts($this->arguments, $imports),
            "#[{$reference}(",
            ')]',
            $indent,
            $reserved,
        );
    }
}
