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

            // The prefix sits on the same line, and a comma follows when the list breaks;
            // both eat into the width this value has to fit in.
            $reserved = strlen($prefix) + 1;

            $parts[] = static fn(int $indent): string => $prefix . $value->render($imports, $indent, $reserved);
        }

        return $parts;
    }
}
