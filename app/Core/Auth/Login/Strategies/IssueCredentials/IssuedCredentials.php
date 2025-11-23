<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Strategies\IssueCredentials;

interface IssuedCredentials
{
    public function getType(): string;

    public function toArray(): array;
}
