<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Strategies\IssueCredentials;

use Illuminate\Contracts\Auth\Authenticatable;

interface IssueCredentialsStrategy
{
    public function execute(Authenticatable $user): IssuedCredentials;
}
