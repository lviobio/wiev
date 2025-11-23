<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Http\Resources;

use App\Core\Auth\Login\Strategies\IssueCredentials\IssuedCredentials;
use App\Http\Resources\JsonResource;
use App\Http\Resources\UserResource;
use App\Models\User;

/**
 * @property-read array{user: User, issued: IssuedCredentials} $resource
 */
class LoginResultResource extends JsonResource
{
    public function toArray($request): array
    {
        ['user' => $user, 'issued' => $issued] = $this->resource;

        return [
            'user' => UserResource::make($user),
            'issued' => [
                'type' => $issued->getType(),
                'credentials' => $issued->toArray(),
            ],
        ];
    }
}
