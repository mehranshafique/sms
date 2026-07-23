<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Middleware\PermissionMiddleware;

/**
 * Legacy assign-permissions routes redirect to the unified roles edit form.
 */
class RolePermissionController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':role.update')->only(['edit', 'update']);
        $this->setPageTitle(__('roles.role_permissions_page_title'));
    }

    public function edit(Role $role)
    {
        $this->checkInstitution($role);

        return redirect()->route('roles.edit', $role);
    }

    public function update(Request $request, Role $role)
    {
        $this->checkInstitution($role);

        return redirect()->route('roles.edit', $role);
    }
}
