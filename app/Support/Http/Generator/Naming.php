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
            EndpointKind::Custom => Str::camel($controllerMethod) . $this->studlySingular,
        };
    }

    public function summary(EndpointKind $kind, string $controllerMethod): string
    {
        return match ($kind) {
            EndpointKind::Index => "List {$this->labelPlural}",
            EndpointKind::Show => "Show {$this->labelSingular}",
            EndpointKind::Store => "Create {$this->labelSingular}",
            EndpointKind::Update => "Update {$this->labelSingular}",
            EndpointKind::Destroy => "Delete {$this->labelSingular}",
            // `removeCover` -> `Remove cover post`, matching the sentence case of the
            // CRUD summaries above rather than headline-casing every word.
            EndpointKind::Custom => Str::ucfirst(Str::lower(Str::headline($controllerMethod)))
                . " {$this->labelSingular}",
        };
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
