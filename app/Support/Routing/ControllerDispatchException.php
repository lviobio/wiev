<?php
declare(strict_types=1);

namespace App\Support\Routing;

use LogicException;

/**
 * Mistakes in how a controller declares its dependencies - programmer errors, surfaced
 * as soon as the route is dispatched rather than as a confusing failure downstream.
 */
final class ControllerDispatchException extends LogicException
{
    public static function parameterIsNotData(string $parameter): self
    {
        return new self(sprintf(
            'Parameter [$%s] uses data-filling attributes but is not typed as a Spatie Data object.',
            $parameter,
        ));
    }
}
