<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Strategies\IssueCredentials;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use LogicException;

class IssueSanctumCredentialsStrategy implements IssueCredentialsStrategy
{
    public function __construct(
        private string $name = 'api',
    )
    {
    }

    public function execute(Authenticatable $user): IssuedCredentials
    {
        if (!$user instanceof HasApiTokensContract) {
            throw new LogicException(sprintf(
                'Authenticatable "%s" must implement "%s" contract',
                get_class($user),
                HasApiTokensContract::class
            ));
        }

        return new IssuedBearerCredentials(
            token: $user->createToken($this->name)->plainTextToken,
        );
    }
}
