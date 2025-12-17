<?php

namespace App\Policies;

use App\Models\Timetable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimetablePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('timetable.viewAny') || $user->can('timetable.view');
    }

    public function view(User $user, Timetable $timetable)
    {
        return $user->can('timetable.view');
    }

    public function create(User $user)
    {
        return $user->can('timetable.create');
    }

    public function update(User $user, Timetable $timetable)
    {
        return $user->can('timetable.update');
    }

    public function delete(User $user, Timetable $timetable)
    {
        return $user->can('timetable.delete');
    }

    public function deleteAny(User $user)
    {
        return $user->can('timetable.deleteAny') || $user->can('timetable.delete');
    }
}