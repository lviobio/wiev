<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use App\Http\Resources\JsonResource;
use App\Support\OpenApi\ResourceArrayKeys;
use OpenApi\Attributes as OA;
use OpenApi\Generator;
use ReflectionClass;

/**
 * A `#[OA\Schema(properties: [...])]` on a JsonResource is hand-written and has no
 * generator keeping it honest the way a Data class's does - `toArray()` isn't statically
 * typed, so there's no type to read off it the way {@see \App\Support\OpenApi\Swagger\ImplicitDataSchema}
 * does for a Data property. This can't infer the types either, but it can catch the
 * field itself drifting: a key added to `toArray()` and never documented, or a
 * documented key that no longer exists.
 */
it('keeps every declared resource schema in sync with what toArray() actually returns', function () {
    // Scanning app_path() autoloads every class in it, including resources nothing else
    // in this test references directly - get_declared_classes() below needs that.
    (new Generator())->generate([app_path()]);

    $resources = array_filter(
        get_declared_classes(),
        static fn (string $class): bool => is_subclass_of($class, JsonResource::class)
            && !(new ReflectionClass($class))->isAbstract()
            && (new ReflectionClass($class))->getAttributes(OA\Schema::class) !== [],
    );

    expect($resources)->not->toBeEmpty();

    $introspector = new ResourceArrayKeys();

    foreach ($resources as $class) {
        $schema = (new ReflectionClass($class))->getAttributes(OA\Schema::class)[0]->newInstance();

        $declared = Generator::isDefault($schema->properties)
            ? []
            : array_map(static fn (OA\Property $property): string => $property->property, $schema->properties);

        $actual = $introspector->describe($class);

        expect(collect($declared)->sort()->values()->all())
            ->toBe(
                collect($actual)->sort()->values()->all(),
                "{$class} declares a #[OA\\Schema] that no longer matches what toArray() returns.",
            );
    }
});
