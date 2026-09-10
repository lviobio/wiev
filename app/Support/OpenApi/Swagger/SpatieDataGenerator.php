<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use App\Support\Http\Generator\Introspection\QueryIntrospector;
use OpenApi\Generator as OpenApiGenerator;
use OpenApi\Pipeline;
use OpenApi\Processors\AugmentDiscriminators;
use OpenApi\Processors\DocBlockDescriptions;

/**
 * Everything this project adds on top of a stock `OpenApi\Generator`, gathered in one
 * place: {@see ImplicitDataSchema} for a Data class that documents itself from its own
 * constructor, {@see SpatieDataTypeResolver} for how a Data property's type reads as a
 * schema, {@see RequiredFromNullability} for which of those properties are required,
 * {@see ListingQueryParameters} for a listing endpoint's page/sort/filter parameters.
 * Both {@see SpatieDataGeneratorFactory} (used by `artisan l5-swagger:generate` once
 * wired up) and {@see \Tests\Feature\Support\OpenApi\SpecMatchesRoutesTest} (the only
 * thing that actually builds a spec today) configure a generator through this rather
 * than assembling the pieces separately, so the two can't quietly drift apart.
 */
final class SpatieDataGenerator
{
    public static function configure(OpenApiGenerator $generator): OpenApiGenerator
    {
        return $generator
            ->setTypeResolver(new SpatieDataTypeResolver())
            ->withProcessorPipeline(static fn (Pipeline $pipeline): Pipeline => $pipeline
                // First: it needs to run before MergeIntoComponents (3rd by default)
                // picks up every top-level schema, and before AugmentSchemas (8th) merges
                // loose #[OA\Property] annotations into whatever schema its class has.
                ->insert(new ImplicitDataSchema(), DocBlockDescriptions::class)
                // Right after AugmentProperties: that's what resolves `$property->property`
                // off its context in the first place - anywhere earlier, every property
                // name is still `Generator::UNDEFINED`.
                ->insert(new RequiredFromNullability(), AugmentDiscriminators::class)
                // Last: only rewrites `parameters`/`x` on operations already built by
                // BuildPaths, and doesn't feed anything any other processor reads.
                ->add(new ListingQueryParameters(app(QueryIntrospector::class))));
    }
}
