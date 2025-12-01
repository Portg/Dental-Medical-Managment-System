<?php

namespace App\Http\Controllers;

use App\Role;
use App\Permission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the form for managing role permissions.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        return view('role-permissions.index', compact('roles', 'permissions'));
    }

    /**
     * Show the form for editing role permissions.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all();
        return view('role-permissions.edit', compact('role', 'permissions'));
    }

    /**
     * Update role permissions.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        $role->permissions()->sync($permissionIds);

        return redirect()->route('role-permissions.edit', $role)
            ->with('success', 'Role permissions updated successfully.');
    }

    /**
     * Assign a specific permission to a role.
     */
    public function assignPermission(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $permission = Permission::findOrFail($validated['permission_id']);
        $role->givePermissionTo($permission);

        return back()->with('success', 'Permission assigned to role successfully.');
    }

    /**
     * Revoke a specific permission from a role.
     */
    public function revokePermission(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $permission = Permission::findOrFail($validated['permission_id']);
        $role->revokePermissionTo($permission);

        return back()->with('success', 'Permission revoked from role successfully.');
    }
}
