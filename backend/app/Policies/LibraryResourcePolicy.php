<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LibraryResource;
use Illuminate\Auth\Access\HandlesAuthorization;

class LibraryResourcePolicy
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

    public function download(?User $user, LibraryResource $resource): bool
    {
        if ($resource->is_validated && $resource->is_public) {
            return true; // public resources downloadable by anyone
        }
        return $user && ($user->id === $resource->uploaded_by || $user->hasPermissionTo('library_validate') || $user->hasPermissionTo('library_download'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('library_upload');
    }

    public function validate(User $user): bool
    {
        return $user->hasPermissionTo('library_validate');
    }

    public function delete(User $user, LibraryResource $resource): bool
    {
        return $user->hasPermissionTo('library_validate') || ($user->id === $resource->uploaded_by && !$resource->is_validated);
    }
}
