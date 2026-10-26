<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BlogPost;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogPostPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Administrateur')) {
            return true;
        }
        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, BlogPost $post): bool
    {
        if ($post->status === 'published') {
            return true;
        }
        return $user && $user->id === $post->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('community_write_blog');
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $user->id === $post->user_id || $user->hasPermissionTo('community_lock_pin');
    }
}
