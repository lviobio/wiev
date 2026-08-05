<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Render;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\GeneratorException;
use Illuminate\Support\Facades\Route;

/**
 * Places the generated route block inside a module's `Http/routes.php`.
 *
 * Unlike the controller, this file is not owned by the generator: it may hold
 * hand-written routes, extra groups, or comments. Only the region between the module's
 * markers is ever rewritten; everything else is preserved exactly.
 */
final readonly class RoutesWriter
{
    private const string MARKER_PREFIX = '@generated-routes';

    /**
     * Alias the controller namespace is imported under, matching the existing
     * `use App\Modules\Post\Http\Controllers as C;` convention.
     */
    private const string CONTROLLER_ALIAS = 'C';

    public function __construct(private RoutesRenderer $renderer = new RoutesRenderer())
    {
    }

    public function build(ControllerDefinition $definition): GeneratedFile
    {
        $path = $definition->routesPath();
        $module = $definition->getRoutesMarker();

        $block = $this->renderer->render(
            $definition,
            self::CONTROLLER_ALIAS . '\\' . $definition->className(),
        );

        $marked = $this->startMarker($module) . PHP_EOL . $block . PHP_EOL . $this->endMarker($module);

        $contents = is_file($path)
            ? $this->merge((string)file_get_contents($path), $marked, $module, $path)
            : $this->scaffold($definition, $marked);

        return new GeneratedFile($path, $this->ensureImports($contents, $definition));
    }

    private function startMarker(string $module): string
    {
        return '// ' . self::MARKER_PREFIX . ':start ' . $module;
    }

    private function endMarker(string $module): string
    {
        return '// ' . self::MARKER_PREFIX . ':end ' . $module;
    }

    /**
     * Replace the marked region, or append one if the file has never been generated into.
     */
    private function merge(string $contents, string $marked, string $module, string $path): string
    {
        $start = preg_quote($this->startMarker($module), '/');
        $end = preg_quote($this->endMarker($module), '/');
        $pattern = "/{$start}.*?{$end}/s";

        $startCount = substr_count($contents, $this->startMarker($module));
        $endCount = substr_count($contents, $this->endMarker($module));

        if ($startCount !== $endCount || $startCount > 1) {
            throw GeneratorException::unbalancedRouteMarkers($path, $module);
        }

        if ($startCount === 1) {
            return (string)preg_replace($pattern, addcslashes($marked, '\\$'), $contents, 1);
        }

        return rtrim($contents, PHP_EOL) . PHP_EOL . PHP_EOL . $marked . PHP_EOL;
    }

    private function scaffold(ControllerDefinition $definition, string $marked): string
    {
        return '<?php' . PHP_EOL
            . 'declare(strict_types=1);' . PHP_EOL
            . PHP_EOL
            . $this->importLines($definition) . PHP_EOL
            . PHP_EOL
            . $marked . PHP_EOL;
    }

    /**
     * Add the imports the block needs, leaving any already present untouched.
     */
    private function ensureImports(string $contents, ControllerDefinition $definition): string
    {
        $missing = array_values(array_filter(
            $this->imports($definition),
            static fn(string $line): bool => !str_contains($contents, $line),
        ));

        if ($missing === []) {
            return $contents;
        }

        $lines = explode(PHP_EOL, $contents);
        $lastUse = null;
        $afterOpening = null;

        foreach ($lines as $index => $line) {
            if (str_starts_with($line, 'use ')) {
                $lastUse = $index;
            }

            if ($afterOpening === null && (str_starts_with($line, 'declare(') || str_starts_with($line, '<?php'))) {
                $afterOpening = $index;
            }
        }

        if ($lastUse !== null) {
            array_splice($lines, $lastUse + 1, 0, $missing);

            return implode(PHP_EOL, $lines);
        }

        array_splice($lines, ($afterOpening ?? 0) + 1, 0, ['', ...$missing]);

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return list<string>
     */
    private function imports(ControllerDefinition $definition): array
    {
        return [
            'use ' . $definition->namespace() . ' as ' . self::CONTROLLER_ALIAS . ';',
            'use ' . Route::class . ';',
        ];
    }

    private function importLines(ControllerDefinition $definition): string
    {
        return implode(PHP_EOL, $this->imports($definition));
    }
}
