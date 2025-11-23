<?php
declare(strict_types=1);

namespace App\Core\Auth\Login;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Timebox;
use SensitiveParameter;

class CredentialsGuard implements Guard
{
    use GuardHelpers;

    private string $name = 'credentials';

    /** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */
    private ?AuthenticatableContract $lastAttempted;

    public function __construct(
        private Timebox      $timebox = new Timebox,
        private bool         $rehashOnLogin = true,
        private int          $timeboxDuration = 200000,
        private Dispatcher   $events,
        EloquentUserProvider $provider,
    )
    {
        $this->provider = $provider;
    }

    public function validate(array $credentials = []): bool
    {
        return $this->timebox->call(function ($timebox) use ($credentials) {
            $this->fireAttemptEvent($credentials);

            $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

            if ($this->hasValidCredentials($user, $credentials)) {
                $this->rehashPasswordIfRequired($user, $credentials);

                $timebox->returnEarly();

                return true;
            }

            $this->fireFailedEvent($user, $credentials);

            return false;
        }, $this->timeboxDuration);
    }

    public function getLastAttempted(): AuthenticatableContract
    {
        return $this->lastAttempted;
    }

    public function user(): ?AuthenticatableContract
    {
        return $this->user;
    }

    protected function hasValidCredentials(?AuthenticatableContract $user, array $credentials): bool
    {
        $validated = !is_null($user) && $this->provider->validateCredentials($user, $credentials);

        if ($validated) {
            $this->fireValidatedEvent($user);
        }

        return $validated;
    }

    protected function rehashPasswordIfRequired(AuthenticatableContract $user, #[SensitiveParameter] array $credentials): void
    {
        if ($this->rehashOnLogin) {
            $this->provider->rehashPasswordIfRequired($user, $credentials);
        }
    }

    protected function fireAttemptEvent(array $credentials): void
    {
        $this->events->dispatch(new Attempting($this->name, $credentials, true));
    }

    protected function fireValidatedEvent(AuthenticatableContract $user): void
    {
        $this->events->dispatch(new Validated($this->name, $user));
    }

    protected function fireFailedEvent(?AuthenticatableContract $user, array $credentials): void
    {
        $this->events->dispatch(new Failed($this->name, $user, $credentials));
    }
}
