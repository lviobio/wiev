<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Modules\Post\Http\Queries\PostIndexQuery;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use Illuminate\Http\Request;

it('leaves the bound request untouched', function () {
    $original = Request::create('/original', 'GET', ['marker' => 'kept']);
    app()->instance('request', $original);

    app(QueryIntrospector::class)->describe(PostIndexQuery::class);

    // Generation runs inside tests and can run inside a request; the stand-in request
    // it binds while reading the query must not outlive the call.
    expect(app('request'))->toBe($original)
        ->and(app('request')->query('marker'))->toBe('kept');
});

it('reads the query through a clean request, not the caller\'s', function () {
    // A sort in the ambient request would make the parent short-circuit and the
    // configured default would go undocumented.
    app()->instance('request', Request::create('/', 'GET', ['sort' => '-title']));

    $descriptor = app(QueryIntrospector::class)->describe(PostIndexQuery::class);

    expect($descriptor->defaultSort)->toBe('id');
});
