<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Strategies\IssueCredentials;

final readonly class IssuedBearerCredentials implements IssuedCredentials
{
    public function __construct(
        private string $token,
    )
    {
    }

    public function getType(): string
    {
        return 'bearer';
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
