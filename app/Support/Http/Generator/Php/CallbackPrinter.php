<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

use App\Support\Http\Generator\Introspection\CallbackSource;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;

/**
 * Prints a parsed callback as a controller method.
 *
 * Fully qualified names are folded back into the controller's imports, so a body written
 * as `$request->input(...)` against `use Illuminate\Http\Request` in the declaration comes
 * out referring to the same short name here - with the matching `use` line emitted.
 */
final class CallbackPrinter
{
    public function render(
        CallbackSource $source,
        string $methodName,
        ImportCollector $imports,
        int $indent,
    ): string {
        $method = new Node\Stmt\ClassMethod($methodName, [
            'flags' => Modifiers::PUBLIC,
            'params' => $source->params,
            'returnType' => $source->returnType,
            'stmts' => $source->stmts,
        ]);

        $aliased = (new NodeTraverser(new AliasNames($imports)))->traverse([$method]);

        $printed = (new PrettyPrinter\Standard())->prettyPrint($aliased);

        return $this->indent($printed, $indent);
    }

    private function indent(string $code, int $indent): string
    {
        $padding = Printer::indent($indent);

        return implode(PHP_EOL, array_map(
            static fn(string $line): string => $line === '' ? '' : $padding . $line,
            explode(PHP_EOL, $code),
        ));
    }
}
