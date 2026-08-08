<?php
declare(strict_types=1);

namespace App\Modules\Post\Policies;

use App\Authorization\HandlesAuthorization;
use App\Authorization\Response;
use App\Models\User;
use App\Modules\Post\Models\Post;

/**
 * Only the author may change a post.
 *
 * Reading is deliberately left unguarded - `viewAny` and `view` are absent, so nothing
 * gates them, which matches the API as it stands.
 */
class PostPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Post $post): Response
    {
        return $this->authorizeOwnership($user, $post, 'edit');
    }

    public function delete(User $user, Post $post): Response
    {
        return $this->authorizeOwnership($user, $post, 'delete');
    }

    public function restore(User $user, Post $post): Response
    {
        return $this->authorizeOwnership($user, $post, 'restore');
    }

    private function authorizeOwnership(User $user, Post $post, string $verb): Response
    {
        return $post->authorUser()->is($user)
            ? $this->allow()
            : $this->deny("Only the author can {$verb} this post.");
    }
}
