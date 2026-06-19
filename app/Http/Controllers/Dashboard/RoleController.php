<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private function superAdminName(): string
    {
        return Config::get('permissions.super_admin_role', 'super_admin');
    }

    private function viewerIsSuperAdmin(): bool
    {
        return Auth::guard('api')->user()->hasRole($this->superAdminName());
    }

    /**
     * Roles list with permission & user counts. The super_admin role is hidden
     * from everyone except super-admins.
     */
    public function index()
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->when(! $this->viewerIsSuperAdmin(), fn($q) => $q->where('name', '!=', $this->superAdminName()))
            ->orderBy('id')
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
            ]);

        return response()->json($roles);
    }

    /**
     * The grouped permission catalog for the management UI.
     */
    public function permissionsCatalog()
    {
        return response()->json(Config::get('permissions.groups'));
    }

    /**
     * Roles available for the user-form dropdown (id + name only).
     */
    public function getAllForUsers()
    {
        $roles = Role::query()
            ->when(! $this->viewerIsSuperAdmin(), fn($q) => $q->where('name', '!=', $this->superAdminName()))
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]);

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        if (strtolower($data['name']) === strtolower($this->superAdminName())) {
            return response()->json(['status' => false, 'message' => 'اسم الدور غير مسموح'], 422);
        }

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'api']);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json(['status' => true, 'data' => ['id' => $role->id]], 201);
    }

    public function show(Role $role)
    {
        if ($this->isProtected($role)) {
            abort(403);
        }

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name'),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($this->isProtected($role)) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        if (strtolower($data['name']) === strtolower($this->superAdminName())) {
            return response()->json(['status' => false, 'message' => 'اسم الدور غير مسموح'], 422);
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json(['status' => true]);
    }

    public function destroy(Role $role)
    {
        if ($role->name === $this->superAdminName()) {
            abort(403);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن حذف دور مرتبط بمستخدمين',
            ], 422);
        }

        $role->delete();

        return response()->json(['status' => true]);
    }

    /**
     * The super_admin role is off-limits to non-super-admins.
     */
    private function isProtected(Role $role): bool
    {
        return $role->name === $this->superAdminName() && ! $this->viewerIsSuperAdmin();
    }
}
