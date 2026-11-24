<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(): View
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('id')->get();
        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissionGroups = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        return view('roles.form', [
            'role' => new Role(),
            'permissionGroups' => $permissionGroups,
            'assignedPermissions' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated =$request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);$this->auditService->log('role.created', $role, ['name' =>$role->name]);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $permissionGroups = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $assignedPermissions =$role->permissions()->pluck('permissions.id')->toArray();

        return view('roles.form', compact('role', 'permissionGroups', 'assignedPermissions'));
    }

    public function update(Request $request, Role$role): RedirectResponse
    {
        $validated =$request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        if ($role->slug !== 'super-admin') {$role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
            $role->permissions()->sync($validated['permissions'] ?? []);
        } else {
            $role->update(['description' =>$validated['description'] ?? null]);
        }

        $this->auditService->log('role.updated', $role, ['name' =>$role->name]);

        return redirect()->route('roles.index')->with('success', 'Role & permissions updated.');
    }
}
