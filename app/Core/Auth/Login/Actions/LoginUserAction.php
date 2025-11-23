<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Actions;

use App\Core\Auth\Login\CredentialsGuard;
use App\Core\Auth\Login\Data\LoginData;
use App\Core\Auth\Login\Data\LoginSuccessfulResultData;
use App\Core\Auth\Login\Exceptions\InvalidCredentialsException;
use App\Core\Auth\Login\Strategies\IssueCredentials\IssueCredentialsStrategy;
use App\Core\Auth\Login\Strategies\IssueCredentials\IssuedCredentials;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\Contracts\HasApiTokens;
use LogicException;

final class LoginUserAction
{
    public function __construct(
        private CredentialsGuard         $guard,
        private IssueCredentialsStrategy $issueCredentialsStrategy,
    )
    {
    }

    public function __invoke(LoginData $data): LoginSuccessfulResultData
    {
        $isValid = $this->guard->validate([
            'email' => $data->email,
            'password' => $data->password,
        ]);

        if (!$isValid) {
            throw InvalidCredentialsException::make();
        }

        $user = $this->guard->getLastAttempted();

        return new LoginSuccessfulResultData(
            user: $user,
            issuedCredentials: $this->issueCredentials($user),
        );
    }

    private function issueCredentials(Authenticatable $user): IssuedCredentials
    {
        if (!$user instanceof HasApiTokens) {
            throw new LogicException(sprintf(
                'Authenticatable "%s" must implement "%s" trait',
                get_class($user),
                HasApiTokens::class
            ));
        }

        return $this->issueCredentialsStrategy->execute($user);
    }
}
