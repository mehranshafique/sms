<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('subject.viewAny') || $user->can('subject.view');
    }

    public function view(User $user, Subject $subject)
    {
        return $user->can('subject.view');
    }

    public function create(User $user)
    {
        return $user->can('subject.create');
    }

    public function update(User $user, Subject $subject)
    {
        return $user->can('subject.update');
    }

    public function delete(User $user, Subject $subject)
    {
        return $user->can('subject.delete');
    }

    public function deleteAny(User $user)
    {
        return $user->can('subject.deleteAny') || $user->can('subject.delete');
    }
}