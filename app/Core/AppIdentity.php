<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

readonly class AppIdentity
{
    private function __construct(
        private Model $model
    )
    {

    }

    public static function fromUser(User $user)
    {
        return new self($user);
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}