<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Exceptions;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Invalid credentials');
    }
}
