<?php
declare(strict_types=1);

namespace App\Support\OpenApi\Swagger;

use L5Swagger\CustomGeneratorInterface;
use OpenApi\Generator as OpenApiGenerator;

/**
 * Plugs {@see SpatieDataGenerator}'s configuration into `artisan l5-swagger:generate`,
 * the same way {@see \Tests\Feature\Support\OpenApi\SpecMatchesRoutesTest} plugs it into
 * the raw `OpenApi\Generator` it builds the spec with for that test.
 *
 * L5Swagger isn't wired up in this project yet - no `config/l5-swagger.php`, no docs
 * route - so nothing references this class today. Once it is, point
 * `scanOptions.generator_factory` at it:
 *
 *   'scanOptions' => [
 *       'generator_factory' => \App\Support\OpenApi\Swagger\SpatieDataGeneratorFactory::class,
 *       ...
 *   ],
 */
final class SpatieDataGeneratorFactory implements CustomGeneratorInterface
{
    public function create(): OpenApiGenerator
    {
        return SpatieDataGenerator::configure(new OpenApiGenerator());
    }
}
