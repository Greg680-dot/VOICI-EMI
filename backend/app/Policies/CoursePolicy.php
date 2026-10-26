<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
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

    public function view(?User $user, Course $course): bool
    {
        if ($course->status === 'published') {
            return true;
        }
        return $user && ($user->id === $course->author_id || $user->hasPermissionTo('lms_manage_course'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('lms_manage_course') || $user->hasPermissionTo('lms_manage_own_course');
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->hasPermissionTo('lms_manage_course')) {
            return true;
        }
        return $user->hasPermissionTo('lms_manage_own_course') && $user->id === $course->author_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasPermissionTo('lms_manage_course');
    }

    public function enroll(User $user, Course $course): bool
    {
        return $user->hasPermissionTo('lms_enroll_course');
    }
}
