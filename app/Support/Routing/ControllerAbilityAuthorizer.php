<?php
declare(strict_types=1);

namespace App\Support\Routing;

use App\Authorization\CheckAuthAbility;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use ReflectionMethod;

/**
 * Enforces the {@see CheckAuthAbility} attributes declared on a controller method.
 *
 * Abilities are resolved through the Gate, which Bouncer hooks into - so roles and
 * abilities granted with `Bouncer::allow()` are honoured without this class knowing
 * anything about Bouncer.
 */
final readonly class ControllerAbilityAuthorizer
{
    public function __construct(private Gate $gate)
    {
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(ReflectionMethod $method, Request $request): void
    {
        foreach ($method->getAttributes(CheckAuthAbility::class) as $attribute) {
            $check = $attribute->newInstance();

            // forUser() rather than the ambient Gate user: the request is the authority
            // on who is acting, and this stays correct under `actingAs` in tests.
            $this->gate
                ->forUser($request->user())
                ->authorize($check->ability(), $check->gateArguments());
        }
    }
}
