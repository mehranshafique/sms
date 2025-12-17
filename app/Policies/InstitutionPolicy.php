<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;

class InstitutionPolicy extends ResourcePolicy
{
    // Inherits all methods (viewAny, view, create, update, delete) 
    // from ResourcePolicy which dynamically checks 'institution.view', 'institution.create', etc.
}