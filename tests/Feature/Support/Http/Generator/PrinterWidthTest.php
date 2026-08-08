<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

use App\Support\Http\Generator\DefinitionLocator;
use App\Support\Http\Generator\Introspection\QueryIntrospector;
use App\Support\Http\Generator\OpenApi\OperationFactory;
use App\Support\Http\Generator\Php\ImportCollector;
use App\Support\Http\Generator\Php\ListLiteral;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use App\Support\Http\Generator\Php\Printer;
use App\Support\Http\Generator\Render\ControllerRenderer;
use OpenApi\Attributes as OA;

it('keeps every generated line within the width budget', function () {
    foreach (app(DefinitionLocator::class)->all() as $definition) {
        $contents = (new ControllerRenderer(new OperationFactory(app(QueryIntrospector::class))))
            ->render($definition)
            ->contents;

        foreach (explode(PHP_EOL, $contents) as $number => $line) {
            expect(strlen($line))->toBeLessThanOrEqual(
                Printer::MAX_WIDTH,
                sprintf('%s line %d is %d chars', $definition->className(), $number + 1, strlen($line)),
            );
        }
    }
});

it('counts the named-argument prefix towards the line width', function () {
    $imports = new ImportCollector('App\Demo');

    // 8 indent + 11 for "responses: " + this list + a trailing comma. Measuring the list
    // alone is what used to let a 148-character line through.
    $list = new ListLiteral(
        new NewExpr(OA\Response::class, [
            'response' => new Literal('204'),
            'description' => new Literal('Post deleted'),
        ]),
        new NewExpr(OA\Response::class, [
            'response' => new Literal('424'),
            'description' => new Literal('Post not found'),
        ]),
    );

    $flat = $list->render($imports, 8);
    $withPrefix = $list->render($imports, 8, strlen('responses: ') + 1);

    expect($flat)->not->toContain(PHP_EOL)
        ->and(8 + strlen($flat))->toBeLessThanOrEqual(Printer::MAX_WIDTH)
        // Same node, same indent - but told the truth about its starting column, it breaks.
        ->and($withPrefix)->toContain(PHP_EOL);
});
