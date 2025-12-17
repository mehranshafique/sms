<?php

namespace App\Policies;

use App\Models\GradeLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradeLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('grade_level.viewAny') || $user->can('grade_level.view');
    }

    public function view(User $user, GradeLevel $gradeLevel)
    {
        return $user->can('grade_level.view');
    }

    public function create(User $user)
    {
        return $user->can('grade_level.create');
    }

    public function update(User $user, GradeLevel $gradeLevel)
    {
        return $user->can('grade_level.update');
    }

    public function delete(User $user, GradeLevel $gradeLevel)
    {
        return $user->can('grade_level.delete');
    }

    public function deleteAny(User $user)
    {
        return $user->can('grade_level.deleteAny') || $user->can('grade_level.delete');
    }
}