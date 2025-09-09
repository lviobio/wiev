<?php
declare(strict_types=1);

namespace App\Authorization;

trait HandlesAuthorization
{
    protected function allow(?string $message = null, mixed $code = null): Response
    {
        return Response::allow($message, $code);
    }

    protected function deny(string $message, mixed $code = null): Response
    {
        return Response::deny($message, $code);
    }
}
