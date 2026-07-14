<?php
declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsNewUser(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    protected function castApiDate(?Carbon $date): ?int
    {
        return $date?->getTimestampMs();
    }
}
