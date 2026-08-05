<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

use OpenApi\Attributes as OA;

/**
 * Generator helper: shorthands for the nodes a declaration adds by hand.
 *
 * Declarations should not have to assemble {@see NewExpr} trees themselves; these cover
 * the pieces that escape hatches actually reach for.
 */
final class Gh
{
    public static function response(int|string $status, string $description): Expr
    {
        return new NewExpr(OA\Response::class, [
            'response' => new Literal((string)$status),
            'description' => new Literal($description),
        ]);
    }

    /**
     * @param  list<string|int>|null  $enum
     */
    public static function queryParameter(
        string $name,
        string $type = 'string',
        ?string $description = null,
        ?array $enum = null,
        ?string $format = null,
        mixed $default = null,
    ): Expr {
        $schema = ['type' => new Literal($type)];

        if ($format !== null) {
            $schema['format'] = new Literal($format);
        }

        if ($default !== null) {
            $schema['default'] = new Literal($default);
        }

        if ($enum !== null) {
            $schema['enum'] = new Literal($enum);
        }

        $arguments = ['name' => new Literal($name)];

        if ($description !== null) {
            $arguments['description'] = new Literal($description);
        }

        $arguments['schema'] = new NewExpr(OA\Schema::class, $schema);

        return new NewExpr(OA\QueryParameter::class, $arguments);
    }

    /**
     * An arbitrary `new Foo(...)` node, for attributes without a shorthand here.
     *
     * @param  class-string  $fqcn
     * @param  array<array-key, Expr>  $arguments
     */
    public static function node(string $fqcn, array $arguments = []): Expr
    {
        return new NewExpr($fqcn, $arguments);
    }

    /**
     * A whole operation attribute, for `Endpoint::openApi()`.
     *
     * @param  array<array-key, Expr>  $arguments
     */
    public static function operation(string $attributeClass, array $arguments): Expr
    {
        return new AttributeExpr($attributeClass, $arguments);
    }

    /**
     * A PHP literal - strings, numbers, booleans, null, and arrays of them.
     */
    public static function value(mixed $value): Expr
    {
        return new Literal($value);
    }

    /**
     * A `Foo::class` reference; the import is registered automatically.
     */
    public static function classRef(string $fqcn): Expr
    {
        return new ClassRef($fqcn);
    }
}
