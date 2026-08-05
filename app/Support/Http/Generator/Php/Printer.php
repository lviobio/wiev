<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * Formatting rules shared by every node.
 *
 * Pint is deliberately not used on generated output: the project ships no `pint.json`,
 * so the `laravel` preset would apply - and its `blank_line_after_opening_tag` rule
 * contradicts this codebase's `declare(strict_types=1);` on line two.
 */
final class Printer
{
    public const int MAX_WIDTH = 140;

    public const int INDENT_SIZE = 4;

    public static function indent(int $level): string
    {
        return str_repeat(' ', $level);
    }

    /**
     * Whether a rendered fragment fits on one line starting at `$indent`.
     */
    public static function fits(string $rendered, int $indent, int $reserved = 0): bool
    {
        return !str_contains($rendered, PHP_EOL)
            && $indent + strlen($rendered) + $reserved <= self::MAX_WIDTH;
    }

    /**
     * Render a comma-separated argument or element list, breaking one item per line
     * only when the single-line form does not fit.
     *
     * Items are passed as renderers rather than finished strings because the indent a
     * child should render at is not known until the parent has decided whether to break.
     *
     * @param  list<callable(int $indent): string>  $parts
     * @param  int  $reserved  Extra columns the caller will append after `$close`.
     */
    public static function joinParts(array $parts, string $open, string $close, int $indent, int $reserved = 0): string
    {
        if ($parts === []) {
            return $open . $close;
        }

        $flat = array_map(static fn(callable $part): string => $part($indent), $parts);
        $singleLine = $open . implode(', ', $flat) . $close;

        if (self::fits($singleLine, $indent, $reserved)) {
            return $singleLine;
        }

        $innerIndent = $indent + self::INDENT_SIZE;
        $padding = self::indent($innerIndent);
        $lines = array_map(
            static fn(callable $part): string => $padding . $part($innerIndent) . ',',
            $parts,
        );

        return $open . PHP_EOL
            . implode(PHP_EOL, $lines) . PHP_EOL
            . self::indent($indent) . $close;
    }
}
