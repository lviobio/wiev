<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Modules\Post\Actions\ShowPost\ShowPostAction;
use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\EndpointPlanner;
use App\Support\Http\Generator\GeneratorException;
use App\Support\Http\Generator\HttpMethod;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Render\ControllerRenderer;
use Illuminate\Http\Request;

function renderCallbackFixture(): string
{
    $definition = app(DefinitionLocator::class)
        ->loadFile(base_path('tests/Fixtures/Http/Generator/callback-endpoints.php'))[0];

    return (new ControllerRenderer(new OperationFactory(app(QueryIntrospector::class))))
        ->render($definition)
        ->contents;
}

it('lifts a closure into a real controller method', function () {
    expect(renderCallbackFixture())->toContain(
        <<<'PHP'
            public function echoTest(Request $request)
            {
                return $request->input('echo');
            }
        PHP,
    );
});

it('turns an arrow function into a method body', function () {
    expect(renderCallbackFixture())->toContain('public function shout(Request $request): string');
});

it('imports the classes the closure names', function () {
    $rendered = renderCallbackFixture();

    // Resolved against the declaration's `use` statements, then re-imported here.
    expect($rendered)->toContain('use Illuminate\Http\Request;')
        ->toContain('use Illuminate\Support\Str;')
        ->toContain('use App\Modules\Post\Models\Post;')
        // ...and written short in the body, not fully qualified.
        ->toContain('return Str::upper((string) $request->input(\'echo\'));')
        ->not->toContain('\Illuminate\Support\Str::upper');
});

it('keeps global helpers as function calls rather than importing them', function () {
    $rendered = renderCallbackFixture();

    // `use response;` would collide with `use Illuminate\Http\Response;` - PHP matches
    // these case-insensitively and the file would refuse to parse.
    expect($rendered)->toContain('return response()->noContent();')
        ->toContain('use Illuminate\Http\Response;')
        ->not->toContain('use response;');
});

it('documents a callback endpoint with a plain 200', function () {
    expect(renderCallbackFixture())
        ->toContain("new OA\\Response(response: '200', description: 'Successful operation')")
        // Nothing is looked up by id, so promising a "not found" would be a lie.
        ->not->toContain("response: '424'");
});

it('refuses a closure that captures variables', function () {
    $captured = 'nope';

    // A capture cannot follow the body into a method, and it is only visible once the
    // source is parsed - so this surfaces at plan time, not at declaration time.
    app(EndpointPlanner::class)->plan(
        Endpoint::make(
            HttpMethod::Get,
            uri: 'bad',
            controllerMethod: 'bad',
            callback: function (Request $request) use ($captured) {
                return $captured;
            },
        ),
    );
})->throws(GeneratorException::class, 'captures variables');

it('refuses a callback declared alongside an action', function () {
    Endpoint::make(
        HttpMethod::Get,
        uri: 'bad',
        controllerMethod: 'bad',
        actionClass: ShowPostAction::class,
        callback: fn(Request $request) => null,
    );
})->throws(GeneratorException::class, 'pick one');
