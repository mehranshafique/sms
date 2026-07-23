<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Module;
use App\Models\InstitutionSetting;
use App\Enums\RoleEnum;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\PermissionRegistrar;

class RolesController extends BaseController
{
    private const PLATFORM_PERMISSION_PREFIXES = [
        'institution.',
        'package.',
        'subscription.',
        'audit_log.',
        'module.',
        'head_officer.',
    ];

    private const PROTECTED_ROLE_NAMES = [
        RoleEnum::SUPER_ADMIN->value,
        RoleEnum::HEAD_OFFICER->value,
        RoleEnum::TEACHER->value,
        RoleEnum::STUDENT->value,
        RoleEnum::SCHOOL_ADMIN->value,
        RoleEnum::GUARDIAN->value,
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':role.viewAny')->only(['index', 'show', 'test']);
        $this->middleware(PermissionMiddleware::class . ':role.create')->only(['create', 'store']);
        $this->middleware(PermissionMiddleware::class . ':role.update')->only(['edit', 'update']);
        $this->middleware(PermissionMiddleware::class . ':role.delete')->only(['destroy']);
        $this->setPageTitle(__('roles.page_title'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
        $institutionId = $this->getInstitutionId();
        $isGlobalMode = $isSuperAdmin && (!$institutionId || $institutionId === 'global');

        if ($request->ajax()) {
            $query = Role::query()
                ->leftJoin('institutions', 'roles.institution_id', '=', 'institutions.id')
                ->select([
                    'roles.*',
                    'institutions.code as institution_code',
                    'institutions.name as institution_name',
                ]);

            $query->withCount(['users' => function ($q) use ($institutionId, $isGlobalMode) {
                if (!$isGlobalMode && $institutionId) {
                    $q->where(function ($inner) use ($institutionId) {
                        $inner->where('users.institute_id', $institutionId)
                            ->orWhereHas('institutes', fn ($iq) => $iq->where('institutions.id', $institutionId));
                    });
                }
            }]);

            if ($isGlobalMode) {
                // Super Admin global context: templates only
                $query->whereNull('roles.institution_id');
            } elseif ($institutionId) {
                // School context (School Admin or Super Admin inside a school): that school only
                $query->where('roles.institution_id', $institutionId);
                if (!$isSuperAdmin) {
                    $query->where('roles.name', '!=', RoleEnum::SUPER_ADMIN->value);
                }
            } else {
                $query->whereRaw('1 = 0');
            }

            $query->latest('roles.created_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    $label = e($row->name);
                    if ($row->isProtectedSystemRole()) {
                        $label .= ' <span class="badge badge-secondary light ms-1">' . e(__('roles.protected')) . '</span>';
                    }
                    return $label;
                })
                ->addColumn('institution', function ($row) use ($isGlobalMode) {
                    if ($row->institution_name) {
                        return e($row->institution_name);
                    }
                    return '<span class="badge badge-secondary">' . e(__('roles.system_global')) . '</span>';
                })
                ->editColumn('users_count', function ($row) {
                    $count = (int) ($row->users_count ?? 0);
                    $badge = $count > 0 ? 'badge-primary' : 'badge-light';
                    return '<span class="badge ' . $badge . '">' . $count . '</span>';
                })
                ->addColumn('action', function ($row) use ($isSuperAdmin, $institutionId, $user) {
                    $btn = '<div class="d-flex justify-content-end action-buttons">';

                    $isGlobal = is_null($row->institution_id);
                    $isOwnInstitutionRole = (int) $row->institution_id === (int) $institutionId;
                    $canEdit = $user->can('role.update') || $isSuperAdmin;
                    $canDelete = $user->can('role.delete') || $isSuperAdmin;

                    $isAssignedToMe = $user->roles->contains('id', $row->id);
                    $isHeadOfficer = $row->name === RoleEnum::HEAD_OFFICER->value;
                    $isSuperAdminRole = $row->name === RoleEnum::SUPER_ADMIN->value;
                    $isOwnSchoolAdminRole = $row->name === RoleEnum::SCHOOL_ADMIN->value
                        && $user->hasRole(RoleEnum::SCHOOL_ADMIN->value)
                        && !$isSuperAdmin;

                    $allowEdit = false;
                    $allowView = false;

                    if ($isSuperAdmin) {
                        if (!$isSuperAdminRole) {
                            $allowEdit = true;
                        }
                    } else {
                        // School Admin may edit any school role's permissions except Head Officer
                        // and their own School Admin permission set (read-only to prevent lockout).
                        if ($isOwnSchoolAdminRole || ($isAssignedToMe && $row->name === RoleEnum::SCHOOL_ADMIN->value)) {
                            $allowView = true;
                        } elseif ($canEdit && $isOwnInstitutionRole && !$isGlobal && !$isHeadOfficer) {
                            $allowEdit = true;
                        }
                    }

                    if ($allowEdit) {
                        $btn .= '<a href="' . route('roles.edit', $row->id) . '" class="btn btn-primary shadow btn-xs sharp me-1" title="' . e(__('roles.edit')) . '"><i class="fa fa-pencil"></i></a>';
                    } elseif ($allowView) {
                        $btn .= '<a href="' . route('roles.edit', $row->id) . '" class="btn btn-info shadow btn-xs sharp me-1" title="' . e(__('roles.view')) . '"><i class="fa fa-eye"></i></a>';
                    }

                    $allowDelete = false;
                    if ($canDelete && !in_array($row->name, self::PROTECTED_ROLE_NAMES, true) && !$isAssignedToMe) {
                        if ($isSuperAdmin || (!$isGlobal && $isOwnInstitutionRole)) {
                            $allowDelete = true;
                        }
                    }

                    if ($allowDelete) {
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="' . $row->id . '" title="' . e(__('roles.delete')) . '"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '<a href="' . route('roles.test', $row->id) . '" class="btn btn-info btn-xs shadow sharp me-1" title="' . e(__('roles.test_role')) . '"><i class="fa fa-shield"></i></a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['name', 'institution', 'users_count', 'action'])
                ->make(true);
        }

        return view('roles.index', compact('isSuperAdmin', 'isGlobalMode'));
    }

    public function create()
    {
        $modules = $this->getAccessiblePermissions();
        return view('roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (!$institutionId || $institutionId === 'global') {
            return response()->json(['message' => __('roles.messages.select_institution')], 422);
        }

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::notIn(self::PROTECTED_ROLE_NAMES),
                Rule::unique('roles')->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId)->where('guard_name', 'web');
                }),
            ],
            'permissions' => 'nullable|array',
        ]);

        if (!Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            $this->verifyPermissionOwnership($request->permissions);
        }

        $role = null;
        DB::transaction(function () use ($request, $institutionId, &$role) {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
                'institution_id' => $institutionId,
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        AuditLogger::log(
            'Create',
            'Roles',
            "Created role \"{$role->name}\" (#{$role->id})",
            null,
            ['role_id' => $role->id, 'permissions' => $request->input('permissions', [])]
        );

        return response()->json(['message' => __('roles.messages.success_create'), 'redirect' => route('roles.index')]);
    }

    public function edit(Role $role)
    {
        $isSuperAdmin = Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value);
        $isReadOnly = false;

        $this->authorizeRoleAccess($role);

        if (!$isSuperAdmin && $role->name === RoleEnum::HEAD_OFFICER->value) {
            abort(403, __('roles.messages.cannot_edit_system'));
        }

        // School Admin may view but not change their own School Admin permission set
        if (!$isSuperAdmin
            && $role->name === RoleEnum::SCHOOL_ADMIN->value
            && Auth::user()->hasRole(RoleEnum::SCHOOL_ADMIN->value)
        ) {
            $isReadOnly = true;
        }

        $modules = $this->getAccessiblePermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'modules', 'rolePermissions', 'isReadOnly'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeRoleAccess($role);
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);

        if (!$isSuperAdmin
            && $role->name === RoleEnum::SCHOOL_ADMIN->value
            && $user->hasRole(RoleEnum::SCHOOL_ADMIN->value)
        ) {
            return response()->json(['message' => __('roles.cannot_update_own_role')], 403);
        }

        if (!$isSuperAdmin && $role->name === RoleEnum::HEAD_OFFICER->value) {
            return response()->json(['message' => __('roles.messages.cannot_edit_system')], 403);
        }

        if ($role->institution_id === null && !$isSuperAdmin) {
            return response()->json(['message' => __('roles.messages.cannot_edit_system')], 403);
        }

        $institutionId = $role->institution_id ?? $this->getInstitutionId();

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles')->ignore($role->id)->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId)->where('guard_name', 'web');
                }),
            ],
            'permissions' => 'nullable|array',
        ]);

        if (!$isSuperAdmin) {
            $this->verifyPermissionOwnership($request->permissions);
        }

        $beforePermissions = $role->permissions->pluck('name')->sort()->values()->all();

        DB::transaction(function () use ($request, $role) {
            if (!in_array($role->name, self::PROTECTED_ROLE_NAMES, true)) {
                $role->update(['name' => $request->name]);
            }

            $role->syncPermissions($request->input('permissions', []));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        AuditLogger::log(
            'Update',
            'Roles',
            "Updated role \"{$role->name}\" (#{$role->id})",
            ['permissions' => $beforePermissions],
            ['permissions' => $request->input('permissions', []), 'name' => $role->fresh()->name]
        );

        return response()->json(['message' => __('roles.messages.success_update'), 'redirect' => route('roles.index')]);
    }

    public function destroy(Role $role)
    {
        $this->authorizeRoleAccess($role);
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);

        if (in_array($role->name, self::PROTECTED_ROLE_NAMES, true)) {
            return response()->json(['message' => __('roles.messages.cannot_delete_system')], 403);
        }

        if (!$isSuperAdmin && $user->roles->contains('id', $role->id)) {
            return response()->json(['message' => __('roles.cannot_delete_own_role')], 403);
        }
        
        if ($role->users()->count() > 0) {
            return response()->json(['message' => __('roles.messages.role_has_users')], 403);
        }

        $snapshot = ['id' => $role->id, 'name' => $role->name, 'institution_id' => $role->institution_id];
        $role->delete();

        AuditLogger::log('Delete', 'Roles', "Deleted role \"{$snapshot['name']}\"", $snapshot, null);

        return response()->json(['message' => __('roles.messages.success_delete')]);
    }

    private function getAccessiblePermissions()
    {
        $user = Auth::user();
        $query = Module::with('permissions')->orderBy('name');
        $allModules = $query->get();

        if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return $allModules->map(function ($module) {
                $module->is_subscribed = true;
                return $module;
            });
        }

        $institutionId = $this->getInstitutionId();
        $enabledModules = [];

        if ($institutionId) {
            $setting = InstitutionSetting::where('institution_id', $institutionId)
                ->where('key', 'enabled_modules')
                ->first();
            $enabledModules = $setting ? json_decode($setting->value, true) : [];
        }

        $coreModules = ['dashboard', 'profile', 'roles', 'permissions', 'settings', 'users'];

        return $allModules->map(function ($module) use ($enabledModules, $coreModules) {
            $slug = Str::slug($module->name, '_');

            if (in_array($slug, $coreModules)) {
                $module->is_subscribed = true;
            } elseif (in_array($slug, $enabledModules)) {
                $module->is_subscribed = true;
            } else {
                $parentGroup = $this->getParentGroup($slug);
                if ($parentGroup && in_array($parentGroup, $enabledModules)) {
                    $module->is_subscribed = true;
                } else {
                    $module->is_subscribed = false;
                }
            }

            // School roles cannot be granted platform-only permissions
            $module->permissions = $module->permissions->reject(function ($permission) {
                foreach (self::PLATFORM_PERMISSION_PREFIXES as $prefix) {
                    if (str_starts_with($permission->name, $prefix)) {
                        return true;
                    }
                }
                return false;
            })->values();

            return $module;
        });
    }

    private function getParentGroup($slug)
    {
        $groups = [
            'academics' => ['academic_sessions', 'grade_levels', 'class_sections', 'subjects', 'timetables', 'enrollments', 'student_promotion', 'students', 'student_attendance'],
            'hr' => ['staff'],
            'examinations' => ['exams', 'exam_marks'],
            'finance' => ['fee_structures', 'fee_types', 'invoices', 'payments'],
            'library' => ['books', 'book_issues'],
            'transport' => ['routes', 'vehicles'],
            'inventory' => ['items', 'suppliers'],
        ];

        foreach ($groups as $groupKey => $modules) {
            if (in_array($slug, $modules)) {
                return $groupKey;
            }
        }

        return null;
    }

    private function verifyPermissionOwnership($requestedPermissions)
    {
        if (empty($requestedPermissions)) {
            return;
        }

        $modules = $this->getAccessiblePermissions();
        $allowed = [];
        foreach ($modules as $m) {
            if ($m->is_subscribed) {
                foreach ($m->permissions as $p) {
                    $allowed[] = $p->name;
                }
            }
        }

        foreach ($requestedPermissions as $perm) {
            foreach (self::PLATFORM_PERMISSION_PREFIXES as $prefix) {
                if (str_starts_with($perm, $prefix)) {
                    abort(403, __('roles.messages.unauthorized_assignment', ['perm' => $perm]));
                }
            }
            if (!in_array($perm, $allowed)) {
                abort(403, __('roles.messages.unauthorized_assignment', ['perm' => $perm]));
            }
        }
    }

    public function test(Role $role)
    {
        $this->authorizeRoleAccess($role);

        $modules = Module::with('permissions')->get();
        $rolePermissions = $role->permissions()->pluck('name')->toArray();

        return view('roles.test', compact('role', 'modules', 'rolePermissions'));
    }

    private function authorizeRoleAccess($role)
    {
        if (Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            return true;
        }

        if (is_null($role->institution_id)) {
            abort(403, __('roles.messages.cannot_edit_system'));
        }

        if ((int) $role->institution_id !== (int) $this->getInstitutionId()) {
            abort(403, __('roles.messages.unauthorized_access'));
        }

        return true;
    }
}
