<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * Turns an argument map into renderers for {@see Printer::joinParts()}.
 */
final class Arguments
{
    /**
     * @param  array<array-key, Expr>  $arguments  String keys become named arguments.
     * @return list<callable(int $indent): string>
     */
    public static function parts(array $arguments, ImportCollector $imports): array
    {
        $parts = [];

        foreach ($arguments as $name => $value) {
            $prefix = is_string($name) ? "{$name}: " : '';

            $parts[] = static fn(int $indent): string => $prefix . $value->render($imports, $indent);
        }

        return $parts;
    }
}
