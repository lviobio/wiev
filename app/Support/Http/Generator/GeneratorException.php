<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use RuntimeException;

final class GeneratorException extends RuntimeException
{
    public static function unrenderableLiteral(string $type): self
    {
        return new self("Cannot render a value of type [{$type}] as a PHP literal.");
    }

    public static function actionIsNotInvokable(string $action): self
    {
        return new self("Action [{$action}] must declare an __invoke() method.");
    }

    public static function actionHasNoDataParameter(string $action): self
    {
        return new self(
            "Action [{$action}]::__invoke() must accept a Spatie Data object as its first parameter; "
            . 'the generator infers the endpoint payload from it.',
        );
    }

    public static function definitionDidNotReturnDefinition(string $path): self
    {
        return new self("[{$path}] must return an instance of " . ControllerDefinition::class . '.');
    }

    public static function missingModel(string $controller): self
    {
        return new self("Definition for [{$controller}] must declare ->model().");
    }

    public static function missingResource(string $controller, string $endpoint): self
    {
        return new self(
            "Endpoint [{$endpoint}] of [{$controller}] returns a model but no ->resource() was declared.",
        );
    }

    public static function queryIsNotIntrospectable(string $query): self
    {
        return new self(
            "Query [{$query}] must extend " . \App\Support\Spatie\QueryBuilder\QueryBuilder::class
            . ' so its filters and sorts can be documented.',
        );
    }

    public static function refusingToOverwrite(string $path): self
    {
        return new self(
            "[{$path}] exists but carries no @generated header. Re-run with --force to overwrite it.",
        );
    }

    public static function unbalancedRouteMarkers(string $path, string $module): self
    {
        return new self("[{$path}] has unbalanced @generated-routes markers for module [{$module}].");
    }

    public static function callbackConflictsWithAction(string $controllerMethod): self
    {
        return new self(
            "Endpoint [{$controllerMethod}] declares both a callback and an action; pick one.",
        );
    }

    public static function callbackHasNoSource(): self
    {
        return new self('A callback endpoint must be a closure written in a declaration file.');
    }

    public static function callbackCapturesVariables(string $path, int $line): self
    {
        return new self(
            "The callback at {$path}:{$line} captures variables with `use`, which cannot be "
            . 'carried into a generated controller method. Inline the values instead.',
        );
    }

    public static function callbackNotFoundInSource(string $path, int $line): self
    {
        return new self("Could not locate the callback declared at {$path}:{$line}.");
    }

    public static function callbackIsAmbiguous(string $path, int $line): self
    {
        return new self(
            "Several callbacks share the same line span at {$path}:{$line}, so the right one "
            . 'cannot be identified. Put each on its own lines.',
        );
    }

    public static function unknownModule(string $module): self
    {
        return new self("No app/**/Http/generator.php found for module [{$module}].");
    }
}
