<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * Verbatim PHP source.
 *
 * The escape hatch for declarations the fluent API cannot express. Acceptable here
 * because generated files are committed and reviewed like any other source.
 */
final readonly class Raw implements Expr
{
    /**
     * @param  list<string>  $uses  Classes the snippet references, so imports are registered.
     */
    public function __construct(
        private string $php,
        private array $uses = [],
    ) {
    }

    public function render(ImportCollector $imports, int $indent): string
    {
        foreach ($this->uses as $fqcn) {
            $imports->reference($fqcn);
        }

        return $this->php;
    }
}
