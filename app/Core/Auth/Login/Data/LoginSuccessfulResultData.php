<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Data;

use App\Core\Auth\Login\Strategies\IssueCredentials\IssuedCredentials;
use Illuminate\Contracts\Auth\Authenticatable;

readonly class LoginSuccessfulResultData
{
    public function __construct(
        public Authenticatable   $user,
        public IssuedCredentials $issuedCredentials,
    )
    {
    }
}
