<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FactCheck;
use Illuminate\Auth\Access\HandlesAuthorization;

class FactCheckPolicy
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
        return true;
    }

    public function view(User $user, FactCheck $factCheck): bool
    {
        if ($user->hasPermissionTo('fact_checking_publish_verdict')) {
            return true;
        }
        return $user->id === $factCheck->user_id;
    }

    public function create(?User $user): bool
    {
        if ($user === null) {
            return true;
        }
        return $user->hasPermissionTo('fact_checking_submit');
    }

    public function runAi(?User $user): bool
    {
        if ($user === null) {
            return true;
        }
        return $user->hasPermissionTo('fact_checking_run_ai');
    }

    public function validateAi(User $user): bool
    {
        return $user->hasPermissionTo('fact_checking_validate_ai');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermissionTo('fact_checking_publish_verdict');
    }
}
