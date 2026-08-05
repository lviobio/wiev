<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

use App\Support\Http\Generator\GeneratorException;

/**
 * A scalar, null, or a (possibly nested) array of them.
 */
final readonly class Literal implements Expr
{
    public function __construct(private mixed $value)
    {
    }

    public function render(ImportCollector $imports, int $indent): string
    {
        return $this->renderValue($this->value, $imports, $indent);
    }

    private function renderValue(mixed $value, ImportCollector $imports, int $indent): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return var_export($value, true);
        }

        if (is_string($value)) {
            return "'" . strtr($value, ["\\" => "\\\\", "'" => "\\'"]) . "'";
        }

        if (is_array($value)) {
            return $this->renderArray($value, $imports, $indent);
        }

        throw GeneratorException::unrenderableLiteral(get_debug_type($value));
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private function renderArray(array $value, ImportCollector $imports, int $indent): string
    {
        $isList = array_is_list($value);

        $parts = [];

        foreach ($value as $key => $item) {
            $prefix = $isList ? '' : $this->renderValue($key, $imports, $indent) . ' => ';

            $parts[] = fn(int $inner): string => $prefix . $this->renderValue($item, $imports, $inner);
        }

        return Printer::joinParts($parts, '[', ']', $indent);
    }
}
