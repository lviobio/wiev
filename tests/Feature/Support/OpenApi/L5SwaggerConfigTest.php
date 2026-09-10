<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use Illuminate\Support\Facades\File;
use L5Swagger\GeneratorFactory;

/**
 * Everything else in this test directory builds a generator through
 * {@see \App\Support\OpenApi\Swagger\SpatieDataGenerator} directly, which proves the
 * custom pipeline itself is correct but not that `artisan l5-swagger:generate` actually
 * uses it - that wiring lives entirely in config/l5-swagger.php's `generator_factory`
 * key, one line nothing else exercises. It was missing for a while: request bodies
 * documented as unresolved `$ref`s and listing endpoints lost their query parameters,
 * because `l5-swagger:generate` was quietly falling back to swagger-php's stock
 * generator. This runs the real config → GeneratorFactory → Generator chain end to end,
 * so that regressing this one line fails a test instead of only showing up in the UI.
 */
it('wires config/l5-swagger.php to the custom generator', function () {
    $docsDir = sys_get_temp_dir().'/l5-swagger-config-test-'.uniqid();

    config(['l5-swagger.documentations.default.paths.docs' => $docsDir]);

    try {
        app(GeneratorFactory::class)->make('default')->generateDocs();

        $spec = json_decode(
            (string)file_get_contents($docsDir.'/api-docs.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    } finally {
        File::deleteDirectory($docsDir);
    }

    // ImplicitDataSchema: CreatePostData documents itself with no #[OA\Schema] of its
    // own, and the request body's $ref resolves to it rather than staying a bare FQCN.
    expect($spec['components']['schemas'])->toHaveKey('CreatePostData')
        ->and($spec['paths']['/api/v1/posts']['post']['requestBody']['content']['multipart/form-data']['schema'])
        ->toBe(['$ref' => '#/components/schemas/CreatePostData']);

    // ListingQueryParameters: the `x` marker expanded into real parameters instead of
    // surviving into the spec unresolved.
    $listParameters = array_column($spec['paths']['/api/v1/posts']['get']['parameters'] ?? [], 'name');

    expect($listParameters)->toContain('page', 'per_page', 'sort', 'filter[title]')
        ->and($spec['paths']['/api/v1/posts']['get']['x'] ?? [])->toBe([]);
});
