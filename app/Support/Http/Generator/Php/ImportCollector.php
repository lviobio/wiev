<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Php;

/**
 * Collects the `use` statements a generated file needs and hands back the shortest
 * safe way to write each class name in its body.
 */
final class ImportCollector
{
    /**
     * Namespaces imported wholesale under an alias instead of class by class.
     *
     * `OpenApi\Attributes` is aliased to `OA` because that is how the whole codebase
     * already writes it, and importing ~10 attribute classes individually would be
     * unreadable.
     *
     * @var array<string, string>
     */
    private const NAMESPACE_ALIASES = [
        'OpenApi\\Attributes' => 'OA',
    ];

    /** @var array<string, string> FQCN => local alias */
    private array $classImports = [];

    /** @var array<string, string> local alias => FQCN, to detect collisions */
    private array $takenAliases = [];

    /** @var array<string, string> namespace => alias */
    private array $namespaceImports = [];

    /**
     * The namespace of the file being generated. Classes living in it need no import.
     */
    public function __construct(private readonly string $currentNamespace = '')
    {
    }

    /**
     * How `$fqcn` should be written in the generated body, registering any import it needs.
     */
    public function reference(string $fqcn): string
    {
        $fqcn = ltrim($fqcn, '\\');

        foreach (self::NAMESPACE_ALIASES as $namespace => $alias) {
            if (str_starts_with($fqcn, $namespace . '\\')) {
                $this->namespaceImports[$namespace] = $alias;

                return $alias . '\\' . substr($fqcn, strlen($namespace) + 1);
            }
        }

        if (isset($this->classImports[$fqcn])) {
            return $this->classImports[$fqcn];
        }

        $shortName = $this->shortNameOf($fqcn);

        if ($this->namespaceOf($fqcn) === $this->currentNamespace) {
            return $shortName;
        }

        // A short name already claimed by a different class: fall back to the fully
        // qualified name rather than inventing an alias nobody would have written.
        if (isset($this->takenAliases[$shortName])) {
            return '\\' . $fqcn;
        }

        $this->classImports[$fqcn] = $shortName;
        $this->takenAliases[$shortName] = $fqcn;

        return $shortName;
    }

    /**
     * The `use` block, alphabetically sorted, without a trailing newline.
     */
    public function render(): string
    {
        $lines = [];

        foreach ($this->classImports as $fqcn => $alias) {
            $lines[$fqcn] = "use {$fqcn};";
        }

        foreach ($this->namespaceImports as $namespace => $alias) {
            $lines[$namespace] = "use {$namespace} as {$alias};";
        }

        ksort($lines, SORT_NATURAL | SORT_FLAG_CASE);

        return implode(PHP_EOL, $lines);
    }

    private function shortNameOf(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');

        return $position === false ? $fqcn : substr($fqcn, $position + 1);
    }

    private function namespaceOf(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');

        return $position === false ? '' : substr($fqcn, 0, $position);
    }
}
