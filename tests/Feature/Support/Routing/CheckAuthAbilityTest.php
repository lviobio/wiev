<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Routing;

use App\Authorization\CheckAuthAbility;
use App\Enums\AuthAbilityEnum;
use App\Models\User;
use App\Modules\Post\Models\Post;
use App\Support\Routing\ControllerAbilityAuthorizer;
use Bouncer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use ReflectionMethod;

/**
 * A stand-in controller: the attribute is read off the method, so a real one is not needed.
 */
final class AbilityFixtureController
{
    #[CheckAuthAbility(AuthAbilityEnum::Access, Post::class)]
    public function scoped(): void
    {
    }

    #[CheckAuthAbility(AuthAbilityEnum::Manage)]
    public function unscoped(): void
    {
    }

    #[CheckAuthAbility(AuthAbilityEnum::Access, Post::class)]
    #[CheckAuthAbility(AuthAbilityEnum::Manage, Post::class)]
    public function both(): void
    {
    }

    public function open(): void
    {
    }
}

function authorizeMethod(string $method, ?User $user): void
{
    $request = Request::create('/');
    $request->setUserResolver(fn() => $user);

    app(ControllerAbilityAuthorizer::class)->authorize(
        new ReflectionMethod(AbilityFixtureController::class, $method),
        $request,
    );
}

it('lets a method without the attribute through', function () {
    authorizeMethod('open', null);
})->throwsNoExceptions();

it('denies a user without the ability', function () {
    authorizeMethod('scoped', User::factory()->create());
})->throws(AuthorizationException::class);

it('allows a user granted the ability on the model class', function () {
    $user = User::factory()->create();
    Bouncer::allow($user)->to(AuthAbilityEnum::Access->value, Post::class);

    authorizeMethod('scoped', $user);
})->throwsNoExceptions();

it('keeps abilities scoped to their model', function () {
    $user = User::factory()->create();
    // Granted on User, asked for on Post.
    Bouncer::allow($user)->to(AuthAbilityEnum::Access->value, User::class);

    authorizeMethod('scoped', $user);
})->throws(AuthorizationException::class);

it('supports an ability with no model at all', function () {
    $user = User::factory()->create();
    Bouncer::allow($user)->to(AuthAbilityEnum::Manage->value);

    authorizeMethod('unscoped', $user);
})->throwsNoExceptions();

it('requires every declared ability when the attribute repeats', function () {
    $user = User::factory()->create();
    Bouncer::allow($user)->to(AuthAbilityEnum::Access->value, Post::class);

    // Only the first of the two is granted.
    authorizeMethod('both', $user);
})->throws(AuthorizationException::class);

it('denies a guest', function () {
    authorizeMethod('scoped', null);
})->throws(AuthorizationException::class);
