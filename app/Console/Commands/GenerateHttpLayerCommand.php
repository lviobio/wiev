<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\GeneratorException;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Render\ControllerRenderer;
use App\Support\Http\Generator\Render\GeneratedFile;
use App\Support\Http\Generator\Render\RoutesWriter;
use Illuminate\Console\Command;

/**
 * Generates module HTTP layers from their `Http/generator.php` declarations.
 */
final class GenerateHttpLayerCommand extends Command
{
    protected $signature = 'http:generate
        {module? : Limit generation to one module or one controller}
        {--check : Fail if any generated file is out of date, without writing}
        {--diff : Print what would change, without writing}
        {--force : Overwrite a controller that carries no @generated header}
        {--no-routes : Skip route files even where the declaration asks for them}';

    protected $description = 'Generate controllers and routes from module HTTP declarations';

    public function handle(DefinitionLocator $locator, QueryIntrospector $queries): int
    {
        try {
            $definitions = $locator->forModule($this->argument('module'));
        } catch (GeneratorException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($definitions === []) {
            $this->components->warn('No app/**/Http/generator.php declarations found.');

            return self::SUCCESS;
        }

        $stale = 0;

        // Processed one at a time, writing as we go: several controllers may share a
        // `routes.php`, and each RoutesWriter needs to see the previous one's block.
        foreach ($definitions as $definition) {
            $stale += $this->process($definition, $queries);
        }

        if ($this->option('check')) {
            if ($stale > 0) {
                $this->components->error("{$stale} generated file(s) are out of date. Run `php artisan http:generate`.");

                return self::FAILURE;
            }

            $this->components->info('Generated HTTP layer is up to date.');
        }

        return self::SUCCESS;
    }

    /**
     * @return int  Number of files that are not up to date.
     */
    private function process(ControllerDefinition $definition, QueryIntrospector $queries): int
    {
        $module = $definition->className();
        $renderer = new ControllerRenderer(new OperationFactory($queries));

        try {
            $files = [$renderer->render($definition)];

            if ($definition->shouldGenerateRoutes() && !$this->option('no-routes')) {
                $files[] = (new RoutesWriter())->build($definition);
            }
        } catch (GeneratorException $exception) {
            $this->components->error("[{$module}] " . $exception->getMessage());

            return 1;
        }

        $this->summarise($definition);

        foreach ($renderer->warnings() as $warning) {
            $this->components->warn("[{$module}] {$warning}");
        }

        $stale = 0;

        foreach ($files as $file) {
            $stale += $this->emit($file, $definition) ? 0 : 1;
        }

        return $stale;
    }

    /**
     * @return bool  Whether the file on disk already matches.
     */
    private function emit(GeneratedFile $file, ControllerDefinition $definition): bool
    {
        if ($file->isUpToDate()) {
            $this->components->twoColumnDetail($file->relativePath(), '<fg=gray>up to date</>');

            return true;
        }

        if ($this->option('check')) {
            $this->components->twoColumnDetail($file->relativePath(), '<fg=yellow>out of date</>');

            return false;
        }

        if ($this->option('diff')) {
            $this->components->twoColumnDetail($file->relativePath(), '<fg=yellow>would change</>');
            $this->line($this->diff($file));

            return false;
        }

        if (!$this->mayWrite($file, $definition)) {
            $this->components->error(GeneratorException::refusingToOverwrite($file->relativePath())->getMessage());

            return false;
        }

        $directory = dirname($file->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }

        file_put_contents($file->path, $file->contents);
        $this->components->twoColumnDetail($file->relativePath(), '<fg=green>written</>');

        return true;
    }

    /**
     * A hand-written controller is never clobbered silently; route files always carry
     * their own markers and are merged rather than replaced.
     */
    private function mayWrite(GeneratedFile $file, ControllerDefinition $definition): bool
    {
        if ($this->option('force') || $file->path !== $definition->controllerPath() || !is_file($file->path)) {
            return true;
        }

        return str_contains((string)file_get_contents($file->path), ControllerRenderer::GENERATED_MARKER);
    }

    private function diff(GeneratedFile $file): string
    {
        $before = is_file($file->path) ? (string)file_get_contents($file->path) : '';
        $temporary = tempnam(sys_get_temp_dir(), 'http-generate');

        file_put_contents($temporary, $file->contents);
        $existing = tempnam(sys_get_temp_dir(), 'http-generate');
        file_put_contents($existing, $before);

        exec(sprintf('diff -u %s %s', escapeshellarg($existing), escapeshellarg($temporary)), $output);

        unlink($temporary);
        unlink($existing);

        return implode(PHP_EOL, $output);
    }

    private function summarise(ControllerDefinition $definition): void
    {
        if (!$this->output->isVerbose()) {
            return;
        }

        $this->components->info($definition->controllerClass);

        foreach ($definition->getEndpoints() as $endpoint) {
            $target = $endpoint->actionClass ?? $endpoint->queryClass ?? '-';

            $this->components->twoColumnDetail(
                sprintf('%s %s', strtoupper($endpoint->method->value), $definition->pathFor($endpoint)),
                class_basename($target),
            );
        }
    }
}
