<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Rewrites resolved class names to whatever the target file imports them as.
 */
final class AliasNames extends NodeVisitorAbstract
{
    /**
     * Marks a name that refers to a function or constant rather than a class.
     */
    private const string NOT_A_CLASS = 'httpGenerator.notAClass';

    public function __construct(private readonly ImportCollector $imports)
    {
    }

    public function enterNode(Node $node): ?Node
    {
        // In a file with no namespace - which every `generator.php` is - the resolver
        // also fully qualifies function and constant names, because there is no global
        // fallback left to be ambiguous about. Importing those as classes would emit a
        // bogus `use response;`, so they have to be told apart here.
        if (($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\ConstFetch)
            && $node->name instanceof Node\Name) {
            $node->name->setAttribute(self::NOT_A_CLASS, true);
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Name\FullyQualified) {
            return null;
        }

        if ($node->getAttribute(self::NOT_A_CLASS) === true) {
            // A bare global helper: leave it unqualified so it reads as written, and so
            // the controller's namespace falls back to global at runtime.
            return count($node->getParts()) === 1 ? new Node\Name($node->toString()) : null;
        }

        return new Node\Name($this->imports->reference($node->toString()));
    }
}
