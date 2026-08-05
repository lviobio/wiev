<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * A node of generated PHP source.
 *
 * Nodes render themselves rather than being pretty-printed from an AST: the output
 * shape here is narrow and highly regular, and rendering directly keeps the emitted
 * code close to what a person would have typed by hand.
 */
interface Expr
{
    /**
     * Render this node.
     *
     * The first line is returned unindented - the caller has already positioned it.
     * Continuation lines are indented relative to `$indent`, which is the column the
     * first line starts at.
     */
    public function render(ImportCollector $imports, int $indent): string;
}
