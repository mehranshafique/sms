<?php

namespace App\Policies;

use App\Models\ClassSection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassSectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('class_section.viewAny') || $user->can('class_section.view');
    }

    public function view(User $user, ClassSection $classSection)
    {
        return $user->can('class_section.view');
    }

    public function create(User $user)
    {
        return $user->can('class_section.create');
    }

    public function update(User $user, ClassSection $classSection)
    {
        return $user->can('class_section.update');
    }

    public function delete(User $user, ClassSection $classSection)
    {
        return $user->can('class_section.delete');
    }

    public function deleteAny(User $user)
    {
        return $user->can('class_section.deleteAny') || $user->can('class_section.delete');
    }
}