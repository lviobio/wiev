<?php
declare(strict_types=1);

namespace App\Support\Data\Filling;

use Attribute;
use Illuminate\Http\Request;
use ReflectionProperty;

/**
 * Fills a Data property with the currently authenticated user.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final readonly class FillFromAuthenticatedUser implements DataPropertyFiller
{
    public function __construct(private string $property)
    {
    }

    public function property(): string
    {
        return $this->property;
    }

    public function resolveValue(Request $request, ReflectionProperty $property): mixed
    {
        return $request->user();
    }
}
