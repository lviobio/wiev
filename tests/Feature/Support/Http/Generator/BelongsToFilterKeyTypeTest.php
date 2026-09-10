<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\FilterParameterFactory;
use App\Support\Spatie\QueryBuilder\QueryBuilder as AppQueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Nothing in the app actually uses `AllowedFilter::belongsTo()` yet, so this is entirely
 * fixture-built - it exercises {@see QueryIntrospector::describe()}'s relation-key-type
 * resolution the way a real belongsTo() filter eventually will.
 */
class BelongsToFilterKeyTypeTestParent extends Model
{
    protected $guarded = [];

    public function related(): BelongsTo
    {
        return $this->belongsTo(BelongsToFilterKeyTypeTestRelated::class);
    }
}

/**
 * A string-keyed related model - the case the old hardcoded `type: 'integer'` got wrong.
 */
class BelongsToFilterKeyTypeTestRelated extends Model
{
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;
}

class BelongsToFilterKeyTypeTestQuery extends AppQueryBuilder
{
    public function __construct()
    {
        parent::__construct(BelongsToFilterKeyTypeTestParent::query());

        $this->allowedFilters(
            AllowedFilter::belongsTo('related'),
            // Renamed for the client - the resolver has to follow the *internal* name
            // (the real relation) rather than the public filter name.
            AllowedFilter::belongsTo('author', 'related'),
            AllowedFilter::belongsTo('nothingHere', 'notARelation'),
        );
    }
}

it('reads the related model\'s own key type instead of assuming integer', function () {
    $query = app(QueryIntrospector::class)->describe(BelongsToFilterKeyTypeTestQuery::class);

    $byName = [];

    foreach ($query->filters as $filter) {
        $byName[$filter->name] = $filter;
    }

    expect($byName['related']->relatedKeyType)->toBe('string')
        ->and($byName['author']->relatedKeyType)->toBe('string')
        ->and($byName['nothingHere']->relatedKeyType)->toBeNull();
});

it('falls back to integer in the documented parameter when the relation could not be resolved', function () {
    $query = app(QueryIntrospector::class)->describe(BelongsToFilterKeyTypeTestQuery::class);
    $factory = new FilterParameterFactory();

    $byName = [];

    foreach ($query->filters as $filter) {
        $byName[$filter->name] = $factory->describe($filter)[0]->type;
    }

    expect($byName['related'])->toBe('string')
        ->and($byName['author'])->toBe('string')
        ->and($byName['nothingHere'])->toBe('integer');
});
