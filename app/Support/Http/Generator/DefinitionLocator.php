<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Finds the `Http/generator.php` declarations across the application.
 *
 * These files declare no class, so swagger-php's `TokenScanner` skips them and they are
 * never reflected - they can live inside `app/` without disturbing the API doc scan.
 */
final class DefinitionLocator
{
    public const string FILENAME = 'generator.php';

    /**
     * Every declared controller, ordered by class name so output is stable.
     *
     * @return list<ControllerDefinition>
     */
    public function all(): array
    {
        $definitions = [];

        $finder = Finder::create()
            ->files()
            ->in(app_path())
            ->name(self::FILENAME)
            ->filter(static fn(SplFileInfo $file): bool => basename($file->getPath()) === 'Http');

        foreach ($finder as $file) {
            $definitions = [...$definitions, ...$this->loadFile($file->getRealPath())];
        }

        usort(
            $definitions,
            static fn(ControllerDefinition $a, ControllerDefinition $b): int
                => strcmp($a->controllerClass, $b->controllerClass),
        );

        return $definitions;
    }

    /**
     * Definitions matching a module name or a single controller name.
     *
     * A module may declare several controllers, so filtering by module can legitimately
     * return more than one.
     *
     * @return list<ControllerDefinition>
     */
    public function forModule(?string $module): array
    {
        $all = $this->all();

        if ($module === null) {
            return $all;
        }

        $matches = array_values(array_filter(
            $all,
            static fn(ControllerDefinition $definition): bool
                => strcasecmp($definition->moduleName(), $module) === 0
                || strcasecmp($definition->className(), $module) === 0,
        ));

        if ($matches === []) {
            throw GeneratorException::unknownModule($module);
        }

        return $matches;
    }

    /**
     * Load a single declaration file, which returns either one definition or several.
     *
     * @return list<ControllerDefinition>
     */
    public function loadFile(string $path): array
    {
        $returned = (static fn(): mixed => require $path)();

        if ($returned instanceof ControllerDefinition) {
            return [$returned];
        }

        if (!is_iterable($returned)) {
            throw GeneratorException::definitionDidNotReturnDefinition($path);
        }

        $definitions = [];

        foreach ($returned as $definition) {
            if (!$definition instanceof ControllerDefinition) {
                throw GeneratorException::definitionDidNotReturnDefinition($path);
            }

            $definitions[] = $definition;
        }

        return $definitions;
    }
}
