<?php
declare(strict_types=1);

namespace App\Support\Data\Filling;

use Illuminate\Http\Request;
use ReflectionProperty;

/**
 * Contract for controller-side attributes that describe how a single
 * property of a Spatie Data object should be filled from the HTTP layer.
 *
 * The filling declaration lives on the controller (the HTTP boundary),
 * keeping the Data object itself free of any HTTP concerns.
 */
interface DataPropertyFiller
{
    /**
     * Name of the target Data property this filler populates.
     */
    public function property(): string;

    /**
     * Resolve the value for the target property from the current request.
     */
    public function resolveValue(Request $request, ReflectionProperty $property): mixed;
}
