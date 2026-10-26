<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Report;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
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
        return $user->hasPermissionTo('community_handle_report');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('community_report');
    }

    public function resolve(User $user): bool
    {
        return $user->hasPermissionTo('community_handle_report');
    }
}
