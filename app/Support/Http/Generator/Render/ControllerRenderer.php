<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Render;

use App\Http\Controllers\Controller;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\EndpointPlanner;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Php\ImportCollector;

/**
 * Renders the whole controller file.
 */
final class ControllerRenderer
{
    /**
     * Marks the file as owned by the generator. Its absence is what stops
     * `http:generate` from clobbering a hand-written controller.
     */
    public const string GENERATED_MARKER = '@generated';

    /** @var list<string> */
    private array $planWarnings = [];

    public function __construct(
        private readonly OperationFactory $operations,
        private readonly EndpointPlanner $planner = new EndpointPlanner(),
    ) {
    }

    public function render(ControllerDefinition $definition): GeneratedFile
    {
        $imports = new ImportCollector($definition->namespace());
        $methodRenderer = new MethodRenderer($this->operations);

        $methods = [];

        foreach ($definition->getEndpoints() as $endpoint) {
            $plan = $this->planner->plan($endpoint, $definition->getRouteParameter());
            $this->planWarnings = [...$this->planWarnings, ...$plan->warnings];

            $methods[] = $methodRenderer->render($definition, $plan, $imports);
        }

        $parentClass = $imports->reference(Controller::class);

        $contents = '<?php' . PHP_EOL
            . 'declare(strict_types=1);' . PHP_EOL
            . PHP_EOL
            . $this->header($definition) . PHP_EOL
            . PHP_EOL
            . 'namespace ' . $definition->namespace() . ';' . PHP_EOL
            . PHP_EOL
            . $imports->render() . PHP_EOL
            . PHP_EOL
            . 'class ' . $definition->className() . ' extends ' . $parentClass . PHP_EOL
            . '{' . PHP_EOL
            . implode(PHP_EOL . PHP_EOL, $methods) . PHP_EOL
            . '}' . PHP_EOL;

        return new GeneratedFile($definition->controllerPath(), $contents);
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return [...$this->planWarnings, ...$this->operations->warnings()];
    }

    /**
     * A block comment, not a docblock: swagger-php's `DocBlockDescriptions` processor
     * would otherwise adopt this text as the class description.
     */
    private function header(ControllerDefinition $definition): string
    {
        $module = $definition->moduleName();
        $source = str_replace(base_path() . '/', '', $this->definitionPath($definition));

        return '/*' . PHP_EOL
            . ' * ' . self::GENERATED_MARKER . " by `php artisan http:generate {$module}` - do not edit." . PHP_EOL
            . " * Edit {$source} and regenerate instead." . PHP_EOL
            . ' */';
    }

    private function definitionPath(ControllerDefinition $definition): string
    {
        return dirname($definition->routesPath()) . '/generator.php';
    }
}
