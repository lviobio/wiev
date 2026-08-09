<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Render;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\GeneratorException;
use App\Support\Http\Generator\Introspection\FillerIntrospector;
use App\Support\Http\Generator\Introspection\ReturnKind;
use App\Authorization\CheckAuthAbility;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Php\AttributeExpr;
use App\Support\Http\Generator\Php\CallbackPrinter;
use App\Support\Http\Generator\Php\ClassRef;
use App\Support\Http\Generator\Php\ImportCollector;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\Printer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Renders one controller method: docblock, OpenAPI attribute, signature and body.
 */
final readonly class MethodRenderer
{
    private const int INDENT = Printer::INDENT_SIZE;

    public function __construct(
        private OperationFactory $operations,
        private FillerIntrospector $fillers = new FillerIntrospector(),
    ) {
    }

    public function render(ControllerDefinition $definition, EndpointPlan $plan, ImportCollector $imports): string
    {
        $indent = Printer::indent(self::INDENT);
        $lines = [];

        if ($plan->endpoint->getDocblock() !== null) {
            $lines[] = $this->docblock($plan->endpoint->getDocblock());
        }

        $operation = $this->operations->build($definition, $plan);

        if ($operation !== null) {
            $lines[] = $indent . $operation->render($imports, self::INDENT);
        }

        foreach ($this->abilityAttributes($plan) as $attribute) {
            $lines[] = $indent . $attribute->render($imports, self::INDENT);
        }

        // A callback endpoint carries its own signature and body, straight from the
        // closure the declaration wrote; there is no Action to delegate to.
        if ($plan->callback !== null) {
            $lines[] = (new CallbackPrinter())->render(
                $plan->callback,
                $plan->endpoint->controllerMethod,
                $imports,
                self::INDENT,
            );

            return implode(PHP_EOL, $lines);
        }

        $lines[] = $this->signature($definition, $plan, $imports);
        $lines[] = $indent . '{';
        $lines[] = $this->body($definition, $plan, $imports);
        $lines[] = $indent . '}';

        return implode(PHP_EOL, $lines);
    }

    /**
     * The declared abilities, rebuilt as attributes on the generated method.
     *
     * The target renders as `Post::class` rather than a quoted string, so the generated
     * file reads like the declaration and the import comes along with it.
     *
     * @return list<AttributeExpr>
     */
    private function abilityAttributes(EndpointPlan $plan): array
    {
        $attributes = [];

        foreach ($plan->endpoint->getAbilities() as $ability) {
            $arguments = [new Literal($ability->ability)];

            if ($ability->target !== null) {
                $arguments[] = new ClassRef($ability->target);
            }

            $attributes[] = new AttributeExpr(CheckAuthAbility::class, $arguments);
        }

        return $attributes;
    }

    private function docblock(string $text): string
    {
        $indent = Printer::indent(self::INDENT);
        $lines = [$indent . '/**'];

        foreach (explode(PHP_EOL, $text) as $line) {
            $lines[] = rtrim($indent . ' * ' . $line);
        }

        $lines[] = $indent . ' */';

        return implode(PHP_EOL, $lines);
    }

    private function signature(ControllerDefinition $definition, EndpointPlan $plan, ImportCollector $imports): string
    {
        $endpoint = $plan->endpoint;
        $indent = Printer::indent(self::INDENT);
        $inner = Printer::indent(self::INDENT * 2);

        $returnType = $imports->reference($this->returnType($definition, $plan));

        /** @var list<string> $parameters Each entry may span several lines. */
        $parameters = [];

        if ($endpoint->queryClass !== null) {
            $parameters[] = $imports->reference($endpoint->queryClass) . ' $query';
        }

        if ($endpoint->actionClass !== null) {
            $parameters[] = $imports->reference($endpoint->actionClass) . ' $action';

            $attributes = array_map(
                fn($filler): string => $this->fillers->toAttributeExpr($filler)->render($imports, self::INDENT * 2),
                $endpoint->fillers(),
            );

            $parameters[] = implode(
                PHP_EOL . $inner,
                [...$attributes, $imports->reference((string)$plan->dataClass) . ' $data'],
            );
        }

        $head = "{$indent}public function {$endpoint->controllerMethod}(";
        $singleLine = $head . implode(', ', $parameters) . "): {$returnType}";

        $isMultiline = array_filter($parameters, static fn(string $p): bool => str_contains($p, PHP_EOL)) !== []
            || strlen($singleLine) > Printer::MAX_WIDTH;

        if (!$isMultiline) {
            return $singleLine;
        }

        $rendered = array_map(static fn(string $p): string => $inner . $p . ',', $parameters);

        return $head . PHP_EOL
            . implode(PHP_EOL, $rendered) . PHP_EOL
            . $indent . '): ' . $returnType;
    }

    /**
     * @return class-string
     */
    private function returnType(ControllerDefinition $definition, EndpointPlan $plan): string
    {
        if ($plan->returnKind === ReturnKind::Void) {
            return Response::class;
        }

        if ($plan->returnKind === ReturnKind::Collection) {
            return AnonymousResourceCollection::class;
        }

        return $definition->getResourceClass()
            ?? throw GeneratorException::missingResource(
                $definition->controllerClass,
                $plan->endpoint->controllerMethod,
            );
    }

    private function body(ControllerDefinition $definition, EndpointPlan $plan, ImportCollector $imports): string
    {
        $indent = Printer::indent(self::INDENT * 2);

        if ($plan->endpoint->getBodyOverride() !== null) {
            foreach ($plan->endpoint->getUses() as $fqcn) {
                $imports->reference($fqcn);
            }

            return implode(
                PHP_EOL,
                array_map(
                    static fn(string $line): string => $line === '' ? '' : $indent . $line,
                    explode(PHP_EOL, $plan->endpoint->getBodyOverride()),
                ),
            );
        }

        if ($plan->returnKind === ReturnKind::Void) {
            return $indent . '$action($data);' . PHP_EOL
                . PHP_EOL
                . $indent . 'return response()->noContent();';
        }

        $resource = $imports->reference((string)$definition->getResourceClass());

        if ($plan->returnKind === ReturnKind::Collection) {
            $paginate = $plan->endpoint->isCursorPaginated() ? 'cursorPaginate' : 'paginate';
            $source = $plan->endpoint->queryClass !== null ? "\$query->{$paginate}()" : '$action($data)';

            return $indent . "return {$resource}::collection({$source});";
        }

        return $indent . "return {$resource}::loaded(\$action(\$data));";
    }
}
