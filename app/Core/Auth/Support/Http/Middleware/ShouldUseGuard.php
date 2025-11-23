<?php
declare(strict_types=1);

namespace App\Core\Auth\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ShouldUseGuard
{
    public function handle(Request $request, Closure $next, string $guard): mixed
    {
        Auth::shouldUse($guard);

        return $next($request);
    }
}
