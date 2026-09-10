<?php
declare(strict_types=1);

namespace App\Support\OpenApi;

use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use ReflectionClass;

/**
 * The keys a `JsonResource::toArray()` actually returns, read from its source rather
 * than run - a resource is built around a real record, and running it needs one.
 *
 * Reads two shapes: a literal `'key' => ...` array entry, and a bare call to one of this
 * project's own `mergeWhenHas*()` helpers ({@see \App\Http\Resources\JsonResource}),
 * each of which is known to contribute a fixed set of keys. Anything else in the
 * returned array is a shape this class doesn't understand, and it says so loudly -
 * a silent guess would defeat the point of a drift check.
 *
 * Exists so a test can catch a `#[OA\Schema(properties: [...])]` that has drifted from
 * the resource it documents - the same way
 * {@see \App\Support\Http\Generator\Introspection\DataIntrospector::undocumentedProperties()}
 * catches a Data property nobody told swagger-php about.
 */
final class ResourceArrayKeys
{
    /**
     * A `mergeWhenHas*()` helper, called bare in the returned array
     * (`$this->mergeWhenHasTimestamps()`, not `'x' => ...`), is known to merge in
     * exactly these keys - the method body itself says so, see the base class.
     *
     * @var array<string, list<string>>
     */
    private const array MERGE_HELPERS = [
        'mergeWhenHasIsTrashed' => ['is_trashed'],
        'mergeWhenHasDeletedAt' => ['deleted_at'],
        'mergeWhenHasTimestamps' => ['created_at', 'updated_at'],
    ];

    /** @var array<string, list<Node\Stmt>> Parsed files, keyed by path. */
    private array $parsed = [];

    /**
     * @param  class-string<JsonResource>  $resourceClass
     * @return list<string>
     */
    public function describe(string $resourceClass): array
    {
        $reflection = new ReflectionClass($resourceClass);
        $path = $reflection->getFileName();

        if ($path === false) {
            throw new LogicException("{$resourceClass} has no source file.");
        }

        $method = $this->findToArray($path, $reflection->getShortName(), $resourceClass);
        $array = $this->returnedArray($resourceClass, $method);

        $keys = [];

        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if ($item->key instanceof Node\Scalar\String_) {
                $keys[] = $item->key->value;

                continue;
            }

            $keys = [...$keys, ...$this->mergedKeys($resourceClass, $item->value)];
        }

        return $keys;
    }

    private function findToArray(string $path, string $shortClassName, string $resourceClass): Node\Stmt\ClassMethod
    {
        // Class_ sits inside a Namespace_ node, not at the top level - a plain foreach
        // over parse()'s result would never see it, hence the recursive finder.
        $class = (new NodeFinder())->findFirst(
            $this->parse($path),
            static fn (Node $node): bool => $node instanceof Node\Stmt\Class_ && (string)$node->name === $shortClassName,
        );

        $method = $class instanceof Node\Stmt\Class_
            ? (new NodeFinder())->findFirst(
                $class->stmts,
                static fn (Node $node): bool => $node instanceof Node\Stmt\ClassMethod && (string)$node->name === 'toArray',
            )
            : null;

        if (!$method instanceof Node\Stmt\ClassMethod) {
            throw new LogicException("Could not find {$resourceClass}::toArray() in {$path}.");
        }

        return $method;
    }

    private function returnedArray(string $resourceClass, Node\Stmt\ClassMethod $method): Node\Expr\Array_
    {
        foreach ($method->stmts ?? [] as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_ && $stmt->expr instanceof Node\Expr\Array_) {
                return $stmt->expr;
            }
        }

        throw new LogicException(
            "{$resourceClass}::toArray() does not return a plain array literal - ResourceArrayKeys can't read its keys.",
        );
    }

    /**
     * @return list<string>
     */
    private function mergedKeys(string $resourceClass, Node\Expr $value): array
    {
        if ($value instanceof Node\Expr\MethodCall && $value->name instanceof Node\Identifier) {
            $helper = self::MERGE_HELPERS[$value->name->toString()] ?? null;

            if ($helper !== null) {
                return $helper;
            }
        }

        throw new LogicException(sprintf(
            "%s::toArray() has an array entry ResourceArrayKeys doesn't understand (a %s with no literal key). "
            . 'Either give it a literal string key, or - if it is a mergeWhenHas*() helper that merges in fixed '
            . 'keys - add it to ResourceArrayKeys::MERGE_HELPERS.',
            $resourceClass,
            $value::class,
        ));
    }

    /**
     * @return list<Node\Stmt>
     */
    private function parse(string $path): array
    {
        if (isset($this->parsed[$path])) {
            return $this->parsed[$path];
        }

        $ast = (new ParserFactory())->createForNewestSupportedVersion()->parse((string)file_get_contents($path));

        if ($ast === null) {
            throw new LogicException("Could not parse {$path}.");
        }

        return $this->parsed[$path] = $ast;
    }
}
