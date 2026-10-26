<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Administrateur')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('admin_manage_users');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermissionTo('admin_manage_users') && !$target->hasRole('Super Administrateur');
    }

    public function suspend(User $user, User $target): bool
    {
        // SA cannot be suspended
        if ($target->hasRole('Super Administrateur')) {
            return false;
        }

        // Admin cannot suspend another Admin
        if ($target->hasRole('Administrateur') && !$user->hasRole('Super Administrateur')) {
            return false;
        }

        if ($user->hasPermissionTo('auth_suspend_user')) {
            return true;
        }

        // Moderator restriction: can only suspend Apprenant or Citoyen
        if ($user->hasPermissionTo('auth_suspend_user_community')) {
            $roles = $target->getRoleNames()->toArray();
            if (empty($roles)) {
                return true;
            }
            foreach ($roles as $role) {
                if (!in_array($role, ['Citoyen', 'Apprenant'])) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    public function manageRoles(User $user, User $target): bool
    {
        if ($target->hasRole('Super Administrateur')) {
            return false;
        }
        return $user->hasPermissionTo('admin_manage_roles') || $user->hasPermissionTo('admin_manage_roles_assign');
    }

    public function viewLogs(User $user, User $target): bool
    {
        if ($target->hasRole('Super Administrateur')) {
            return false;
        }
        return $user->hasPermissionTo('auth_view_other_logs');
    }

    public function forceLogout(User $user, User $target): bool
    {
        if ($target->hasRole('Super Administrateur')) {
            return false;
        }
        if ($target->hasRole('Administrateur') && !$user->hasRole('Super Administrateur')) {
            return false;
        }
        return $user->hasPermissionTo('auth_revoke_sessions');
    }
}
