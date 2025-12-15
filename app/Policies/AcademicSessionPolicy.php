<?php

namespace App\Policies;

use App\Models\AcademicSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('academic_session.viewAny') || $user->can('academic_session.view');
    }

    public function view(User $user, AcademicSession $session)
    {
        return $user->can('academic_session.view');
    }

    public function create(User $user)
    {
        return $user->can('academic_session.create');
    }

    public function update(User $user, AcademicSession $session)
    {
        return $user->can('academic_session.update');
    }

    public function delete(User $user, AcademicSession $session)
    {
        return $user->can('academic_session.delete');
    }

    public function deleteAny(User $user)
    {
        return $user->can('academic_session.deleteAny') || $user->can('academic_session.delete');
    }
}