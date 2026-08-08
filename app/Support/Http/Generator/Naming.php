<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use Illuminate\Support\Str;

/**
 * Every piece of wording the generator invents, in one place.
 *
 * Derived from the model class name so that declaring `->model(Post::class)` is enough
 * to produce route prefixes, operation ids, summaries and response descriptions that
 * match what a person would have written.
 */
final readonly class Naming
{
    /** e.g. `Post` */
    public string $studlySingular;

    /** e.g. `Posts` */
    public string $studlyPlural;

    /** e.g. `post` */
    public string $snakeSingular;

    /** e.g. `posts` */
    public string $kebabPlural;

    /** e.g. `post` - used in prose */
    public string $labelSingular;

    /** e.g. `posts` - used in prose */
    public string $labelPlural;

    /** e.g. `Post` - used to open a sentence */
    public string $titleSingular;

    public function __construct(string $modelClass)
    {
        $base = class_basename($modelClass);

        $this->studlySingular = Str::studly(Str::singular($base));
        $this->studlyPlural = Str::plural($this->studlySingular);
        $this->snakeSingular = Str::snake($this->studlySingular);
        $this->kebabPlural = Str::kebab($this->studlyPlural);
        $this->labelSingular = Str::lower(Str::headline($this->studlySingular));
        $this->labelPlural = Str::lower(Str::headline($this->studlyPlural));
        $this->titleSingular = Str::headline($this->studlySingular);
    }

    public function operationId(EndpointKind $kind, string $controllerMethod): string
    {
        return match ($kind) {
            EndpointKind::Index => 'get' . $this->studlyPlural,
            EndpointKind::Show => 'get' . $this->studlySingular,
            EndpointKind::Store => 'create' . $this->studlySingular,
            EndpointKind::Update => 'update' . $this->studlySingular,
            EndpointKind::Destroy => 'delete' . $this->studlySingular,
            EndpointKind::Custom => $this->customOperationId($controllerMethod),
        };
    }

    /**
     * The noun goes after the verb, not at the end: `removeCover` reads as
     * `removePostCover`, while a bare `restore` still reads as `restorePost`.
     *
     * It has to appear somewhere - OpenAPI operation ids are global, and two modules
     * are free to both declare a `removeCover`.
     */
    private function customOperationId(string $controllerMethod): string
    {
        $words = explode(' ', Str::headline($controllerMethod));
        $verb = Str::camel((string)array_shift($words));

        return $verb . $this->studlySingular . implode('', array_map(Str::studly(...), $words));
    }

    public function summary(EndpointKind $kind, string $controllerMethod): string
    {
        return match ($kind) {
            EndpointKind::Index => "List {$this->labelPlural}",
            EndpointKind::Show => "Show {$this->labelSingular}",
            EndpointKind::Store => "Create {$this->labelSingular}",
            EndpointKind::Update => "Update {$this->labelSingular}",
            EndpointKind::Destroy => "Delete {$this->labelSingular}",
            EndpointKind::Custom => $this->customSummary($controllerMethod),
        };
    }

    /**
     * A one-word method needs the noun to mean anything (`restore` -> `Restore post`),
     * but a multi-word one already names its object, and appending would produce
     * `Remove cover post`.
     */
    private function customSummary(string $controllerMethod): string
    {
        $headline = Str::headline($controllerMethod);
        $sentence = Str::ucfirst(Str::lower($headline));

        return str_contains($headline, ' ') ? $sentence : "{$sentence} {$this->labelSingular}";
    }

    /**
     * Description of the success response, or null to keep the response class default.
     */
    public function successDescription(EndpointKind $kind): ?string
    {
        return match ($kind) {
            EndpointKind::Store => "{$this->titleSingular} created",
            EndpointKind::Update => "{$this->titleSingular} updated",
            EndpointKind::Destroy => "{$this->titleSingular} deleted",
            default => null,
        };
    }

    public function notFoundDescription(): string
    {
        return "{$this->titleSingular} not found";
    }

    public function trashedFilterDescription(): string
    {
        return "Include soft-deleted {$this->labelPlural}";
    }
}
