<?php
declare(strict_types=1);

namespace App\Authorization\Policies;

use App\Authorization\Response;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function index(?User $user): Response
    {
        return Response::allow();
    }

    public function store(?User $user): Response
    {
        return Response::allow();
    }

    public function update(?User $user): Response
    {
        return Response::allow();
    }

    public function destroy(?User $user): Response
    {
        return Response::allow();
    }
}
