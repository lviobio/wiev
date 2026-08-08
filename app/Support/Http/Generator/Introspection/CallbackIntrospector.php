<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use App\Support\Http\Generator\GeneratorException;
use Closure;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use ReflectionFunction;

/**
 * Recovers the source of a closure written inline in a declaration.
 *
 * Reflection alone only yields the file and line span, which is not enough to re-emit the
 * body safely. Parsing the declaration file gives the exact node, and running the parser's
 * name resolver over it turns short class names into fully qualified ones - so a closure
 * relying on `generator.php`'s `use` statements still prints correctly inside a controller
 * that imports different things.
 */
final class CallbackIntrospector
{
    /** @var array<string, list<Node\Stmt>> Parsed declaration files, keyed by path. */
    private array $parsed = [];

    public function describe(Closure $callback): CallbackSource
    {
        $reflection = new ReflectionFunction($callback);
        $path = $reflection->getFileName();

        if ($path === false) {
            throw GeneratorException::callbackHasNoSource();
        }

        $node = $this->findNode($path, $reflection->getStartLine(), $reflection->getEndLine());

        if ($node instanceof Node\Expr\Closure && $node->uses !== []) {
            throw GeneratorException::callbackCapturesVariables($path, $reflection->getStartLine());
        }

        $stmts = $node instanceof Node\Expr\ArrowFunction
            ? [new Node\Stmt\Return_($node->expr)]
            : $node->stmts;

        return new CallbackSource(
            params: $node->params,
            returnType: $node->returnType,
            stmts: $stmts,
        );
    }

    private function findNode(string $path, int $startLine, int $endLine): Node\Expr\Closure|Node\Expr\ArrowFunction
    {
        $collector = new class extends NodeVisitorAbstract {
            /** @var array<string, list<Node\Expr\Closure|Node\Expr\ArrowFunction>> */
            public array $nodes = [];

            public function enterNode(Node $node): ?Node
            {
                if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
                    $this->nodes[$node->getStartLine() . ':' . $node->getEndLine()][] = $node;
                }

                return null;
            }
        };

        (new NodeTraverser($collector))->traverse($this->parse($path));

        $matches = $collector->nodes[$startLine . ':' . $endLine] ?? [];

        if ($matches === []) {
            throw GeneratorException::callbackNotFoundInSource($path, $startLine);
        }

        // Two closures spanning exactly the same lines cannot be told apart by reflection.
        if (count($matches) > 1) {
            throw GeneratorException::callbackIsAmbiguous($path, $startLine);
        }

        return $matches[0];
    }

    /**
     * @return list<Node\Stmt>
     */
    private function parse(string $path): array
    {
        if (isset($this->parsed[$path])) {
            return $this->parsed[$path];
        }

        $ast = (new ParserFactory())
            ->createForNewestSupportedVersion()
            ->parse((string)file_get_contents($path));

        if ($ast === null) {
            throw GeneratorException::callbackNotFoundInSource($path, 0);
        }

        return $this->parsed[$path] = (new NodeTraverser(new NameResolver()))->traverse($ast);
    }
}
