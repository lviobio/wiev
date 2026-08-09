<?php
declare(strict_types=1);

namespace Tests;

use App\Enums\AuthAbilityEnum;
use App\Models\User;
use Bouncer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsNewUser(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    /**
     * Grant an ability to the acting user.
     *
     * Abilities are not granted by default, so a test that exercises a
     * `#[CheckAuthAbility]`-guarded endpoint has to ask for them - which keeps the
     * denial cases honest.
     *
     * @param  class-string|null  $target  Model class the ability is scoped to.
     */
    protected function allowActingUser(AuthAbilityEnum $ability, ?string $target = null): static
    {
        $user = $this->app['auth']->user();

        Bouncer::allow($user)->to($ability->value, $target);
        Bouncer::refreshFor($user);

        return $this;
    }

    /**
     * Act as a fresh user that already holds an ability.
     */
    protected function actingAsUserAllowedTo(AuthAbilityEnum $ability, ?string $target = null): static
    {
        return $this->actingAsNewUser()->allowActingUser($ability, $target);
    }

    protected function castApiDate(?Carbon $date): ?int
    {
        return $date?->getTimestampMs();
    }
}
