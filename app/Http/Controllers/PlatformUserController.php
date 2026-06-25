<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class PlatformUserController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(RoleMiddleware::class . ':Super Admin');
        $this->setPageTitle(__('platform_users.page_title'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::query()
                ->with(['roles', 'institute'])
                ->select('users.*')
                ->latest('users.created_at');

            if ($request->filled('institution_id')) {
                $query->where('institute_id', $request->institution_id);
            }

            if ($request->filled('role')) {
                $role = $request->role;
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            }

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('details', function ($row) {
                    $initial = strtoupper(substr($row->name ?: '?', 0, 1));

                    return '<div class="d-flex align-items-center">'
                        . '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;font-weight:700;">' . e($initial) . '</div>'
                        . '<div><h6 class="mb-0">' . e($row->name) . '</h6>'
                        . '<small class="text-muted">' . e($row->email) . '</small></div></div>';
                })
                ->addColumn('username', fn ($row) => e($row->username ?? '—'))
                ->addColumn('institution', fn ($row) => e($row->institute->name ?? '—'))
                ->addColumn('roles', function ($row) {
                    return $row->roles->pluck('name')->unique()->map(function ($name) {
                        return '<span class="badge badge-primary light me-1">' . e($name) . '</span>';
                    })->implode(' ');
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success">' . __('platform_users.active') . '</span>'
                        : '<span class="badge badge-danger">' . __('platform_users.inactive') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $rolesJson = htmlspecialchars($row->roles->pluck('name')->values()->toJson(), ENT_QUOTES, 'UTF-8');

                    return '<div class="d-flex justify-content-end gap-1">'
                        . '<button type="button" class="btn btn-primary btn-sm shadow edit-roles-btn" '
                        . 'data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-roles="' . $rolesJson . '">'
                        . '<i class="fa fa-user-shield"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['details', 'roles', 'is_active', 'action'])
                ->make(true);
        }

        $institutions = Institution::orderBy('name')->pluck('name', 'id');
        $roles = Role::query()
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->pluck('name', 'name');

        return view('platform_users.index', compact('institutions', 'roles'));
    }

    public function updateRoles(Request $request, User $user)
    {
        $availableRoles = Role::query()->select('name')->distinct()->pluck('name')->all();

        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => ['string', Rule::in($availableRoles)],
        ]);

        if ($user->id === Auth::id() && !in_array(RoleEnum::SUPER_ADMIN->value, $request->roles, true)) {
            return response()->json(['message' => __('platform_users.cannot_remove_own_super_admin')], 422);
        }

        $user->syncRoles($request->roles);
        $this->syncUserTypeFromRoles($user);

        return response()->json(['message' => __('platform_users.roles_updated')]);
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['message' => __('platform_users.cannot_deactivate_self')], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => __('platform_users.status_updated'),
            'is_active' => $user->is_active,
        ]);
    }

    private function syncUserTypeFromRoles(User $user): void
    {
        $user->load('roles');
        $names = $user->roles->pluck('name');

        $type = match (true) {
            $names->contains(RoleEnum::SUPER_ADMIN->value) => UserType::SUPER_ADMIN,
            $names->contains(RoleEnum::HEAD_OFFICER->value) => UserType::HEAD_OFFICER,
            $names->contains(RoleEnum::SCHOOL_ADMIN->value) => UserType::SCHOOL_ADMIN,
            $names->contains(RoleEnum::STUDENT->value) => UserType::STUDENT,
            $names->contains(RoleEnum::GUARDIAN->value) => UserType::GUARDIAN,
            default => UserType::STAFF,
        };

        $user->forceFill(['user_type' => $type->value])->save();
    }
}
