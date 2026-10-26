<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ForumTopic;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumTopicPolicy
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

    public function view(?User $user, ForumTopic $topic): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('community_write');
    }

    public function update(User $user, ForumTopic $topic): bool
    {
        if ($user->hasPermissionTo('community_lock_pin')) {
            return true;
        }
        return $user->hasPermissionTo('community_write') && $user->id === $topic->user_id && !$topic->is_locked;
    }

    public function delete(User $user, ForumTopic $topic): bool
    {
        return $user->hasPermissionTo('community_lock_pin');
    }

    public function lockOrPin(User $user): bool
    {
        return $user->hasPermissionTo('community_lock_pin');
    }
}
