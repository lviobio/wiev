<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersTrashed;

it('reads sorts, filters and page sizes off a listing query', function () {
    $descriptor = app(QueryIntrospector::class)->describe(PostIndexQuery::class);

    expect($descriptor->sorts)->toBe(['id', 'title', 'created_at'])
        ->and($descriptor->defaultSort)->toBe('id')
        ->and($descriptor->allowedPerPage)->toBe([15, 25, 50, 100])
        ->and($descriptor->filters)->toHaveCount(4);
});

it('recognises filter strategies and carries explicit descriptors', function () {
    $filters = collect(app(QueryIntrospector::class)->describe(PostIndexQuery::class)->filters)
        ->keyBy(fn($filter) => $filter->name);

    // Recognised by strategy class - no descriptor needed.
    expect($filters['title']->filterClass)->toBe(FiltersPartial::class)
        ->and($filters['title']->descriptors)->toBeEmpty()
        ->and($filters['trashed']->filterClass)->toBe(FiltersTrashed::class)
        ->and($filters['trashed']->descriptors)->toBeEmpty();

    // Callback-backed filters are indistinguishable by reflection, so they describe
    // themselves - and dateRange expands into two parameters.
    expect($filters['search']->descriptors)->toHaveCount(1)
        ->and($filters['search']->descriptors[0]->description)
        ->toBe('Partial match against title and content')
        ->and($filters['created_at']->descriptors)->toHaveCount(2)
        ->and($filters['created_at']->descriptors[0]->parameterName('filter', 'created_at'))
        ->toBe('filter[created_at][from]')
        ->and($filters['created_at']->descriptors[1]->parameterName('filter', 'created_at'))
        ->toBe('filter[created_at][to]');
});
