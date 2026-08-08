<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

use PhpParser\Node;

/**
 * The parsed source of a callback declared inline in `generator.php`.
 *
 * Class names have already been resolved against that file's `use` statements, so the
 * nodes carry fully qualified names and can be re-printed under the controller's own
 * imports without ambiguity.
 */
final readonly class CallbackSource
{
    /**
     * @param  list<Node\Param>  $params
     * @param  list<Node\Stmt>  $stmts
     */
    public function __construct(
        public array $params,
        public ?Node $returnType,
        public array $stmts,
    ) {
    }
}
