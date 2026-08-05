<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\Introspection\FilterIntrospection;
use App\Support\Http\Generator\Naming;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use App\Support\Spatie\QueryBuilder\FilterParameterDescriptor;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\Filters\FiltersBeginsWith;
use Spatie\QueryBuilder\Filters\FiltersBelongsTo;
use Spatie\QueryBuilder\Filters\FiltersEndsWith;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersTrashed;

/**
 * Turns one allowed filter into the query parameters that drive it.
 */
final class FilterParameterFactory
{
    /** @var list<string> */
    private array $warnings = [];

    /**
     * @return list<Expr>
     */
    public function build(FilterIntrospection $filter, string $filterParameter, Naming $naming): array
    {
        foreach ($this->descriptorsFor($filter, $naming) as $descriptor) {
            $parameters[] = $this->toParameter($descriptor, $filter, $filterParameter);
        }

        return $parameters ?? [];
    }

    /**
     * Warnings raised while building, e.g. filters that could not be recognised.
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return list<FilterParameterDescriptor>
     */
    private function descriptorsFor(FilterIntrospection $filter, Naming $naming): array
    {
        if ($filter->descriptors !== []) {
            return $filter->descriptors;
        }

        return match ($filter->filterClass) {
            FiltersPartial::class, FiltersBeginsWith::class, FiltersEndsWith::class => [
                new FilterParameterDescriptor(description: "Partial {$filter->name} match"),
            ],
            FiltersTrashed::class => [
                new FilterParameterDescriptor(
                    description: $naming->trashedFilterDescription(),
                    enum: ['with', 'only'],
                ),
            ],
            FiltersExact::class => [new FilterParameterDescriptor()],
            FiltersBelongsTo::class => [new FilterParameterDescriptor(type: 'integer')],
            default => $this->unrecognised($filter),
        };
    }

    /**
     * @return list<FilterParameterDescriptor>
     */
    private function unrecognised(FilterIntrospection $filter): array
    {
        $this->warnings[] = sprintf(
            'Filter [%s] is backed by %s and was documented as a plain string; '
            . 'call ->openApi(new FilterParameterDescriptor(...)) on it to describe it properly.',
            $filter->name,
            class_basename($filter->filterClass),
        );

        return [new FilterParameterDescriptor()];
    }

    private function toParameter(
        FilterParameterDescriptor $descriptor,
        FilterIntrospection $filter,
        string $filterParameter,
    ): Expr {
        $schema = ['type' => new Literal($descriptor->type)];

        if ($descriptor->format !== null) {
            $schema['format'] = new Literal($descriptor->format);
        }

        if ($descriptor->enum !== null) {
            $schema['enum'] = new Literal($descriptor->enum);
        }

        $arguments = ['name' => new Literal($descriptor->parameterName($filterParameter, $filter->name))];

        if ($descriptor->description !== null) {
            $arguments['description'] = new Literal($descriptor->description);
        }

        $arguments['schema'] = new NewExpr(OA\Schema::class, $schema);

        return new NewExpr(OA\QueryParameter::class, $arguments);
    }
}
