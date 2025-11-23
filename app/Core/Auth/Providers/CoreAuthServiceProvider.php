<?php
declare(strict_types=1);

namespace App\Core\Auth\Providers;

use App\Core\Auth\Login\CredentialsGuard;
use App\Core\Auth\Login\Strategies\IssueCredentials\IssueCredentialsStrategy;
use App\Core\Auth\Login\Strategies\IssueCredentials\IssueSanctumCredentialsStrategy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Timebox;
use Symfony\Component\HttpFoundation\Response;

class CoreAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->after(function (Response $response) {
                    return $response->getStatusCode() === 422;
                });
        });
    }

    public function register(): void
    {
        $this->app->bind(IssueCredentialsStrategy::class, IssueSanctumCredentialsStrategy::class);

        $this->app->bind(CredentialsGuard::class, fn() => new CredentialsGuard(
            timebox: $this->app[Timebox::class],
            rehashOnLogin: true,
            timeboxDuration: 200000,
            events: $this->app[Dispatcher::class],
            provider: Auth::createUserProvider('users'),
        ));
    }
}
