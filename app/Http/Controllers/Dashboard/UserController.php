<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;
use Spatie\Permission\Models\Role;

class UserController extends Controller
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
     * Super-admin users and hidden users are only visible to super-admins.
     */
    private function isConcealed(User $user): bool
    {
        return ($user->hidden || $user->hasRole($this->superAdminName())) && ! $this->viewerIsSuperAdmin();
    }

    public function index(Request $request)
    {
        $size = $request->size ?? 10;
        $orderBy = $request->orderBy ?? 'id';
        $orderDirection = $request->orderDir ?? 'desc';

        $users = User::when($request->name, function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->name . '%');
        })->when($request->username, function ($q) use ($request) {
            $q->where('username', 'like', '%' . $request->username . '%');
        })->when($request->role, function ($q) use ($request) {
            $q->whereHas('roles', function ($q) use ($request) {
                $q->where('id', $request->role);
            });
        })
        // Conceal super-admins and hidden users from everyone but super-admins.
        ->when(! $this->viewerIsSuperAdmin(), function ($q) {
            $q->where('hidden', false)
              ->whereDoesntHave('roles', fn($r) => $r->where('name', $this->superAdminName()));
        })
        ->orderBy($orderBy, $orderDirection)->paginate($size);

        $users->getCollection()->transform(function ($user) {
            return [
                "id" => $user->id,
                "username" => $user->username,
                "name" => $user->name,
                "image" => $user->image_url,
                "role" => $user->getRoleNames()->first() ?? 'لا يوجد دور',
                "is_online" => $user->is_online,
                "current_page" => $user->is_online ? $user->current_page : null,
            ];
        });

        return response()->json($users);
    }

    public function show(User $user)
    {
        if ($this->isConcealed($user)) {
            abort(403);
        }

        return response()->json([
            "id" => $user->id,
            "username" => $user->username,
            "name" => $user->name,
            "image" => $user->image_url,
            "role" => $user->roles()->first()->id,
            "branches" => $user->branches()->pluck('branches.id'),
            "hidden" => $user->hidden,
        ]);
    }

    public function store(Request $request) {
        $userData = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:'. (4 * 1024 * 1024),
            'name' => 'required|string|min:2|max:25',
            'username' => ['required', 'string', 'min:5', 'max:18', 'regex:/^[A-Za-z][A-Za-z0-9]*$/', 'unique:users'],
            'password' => [
                'required',
                'string',
                Password::min(9)->max(35)->letters()->numbers()
            ],
            'role' => 'required|string|exists:roles,id',
            'branches' => 'nullable|array',
            'branches.*' => 'integer|exists:branches,id',
            'hidden' => 'nullable|boolean',
        ]);

        $role = Role::findOrFail($request->role);
        $this->guardSuperAdminRole($role);

        $image = $request->file('image');
        $imagePath = 'storage/users/' . uniqid() . '.webp';

        $manager = new ImageManager(new Driver());
        $manager->read($image)
            ->scale(width: 450)
            ->encode(new AutoEncoder('webp', quality: 75))
            ->save($imagePath);

        $userData['image'] = $imagePath;

        unset($userData['branches']);

        // Only super-admins may hide users.
        $userData['hidden'] = $this->viewerIsSuperAdmin() ? $request->boolean('hidden') : false;

        $user = User::create($userData);

        $user->assignRole($role);

        $user->branches()->sync($request->input('branches', []));

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
            ]
        ], 201);
    }

    public function update(Request $request, User $user) {
        if ($this->isConcealed($user)) {
            abort(403);
        }

        $userData = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'. (4 * 1024 * 1024),
            'name' => 'required|string|min:2|max:25',
            'username' => ['required', 'string', 'min:5', 'max:18', 'regex:/^[A-Za-z][A-Za-z0-9]*$/', 'unique:users,username,' . $user->id],
            'password' => [
                'nullable',
                'string',
                Password::min(9)->max(35)->letters()->numbers()
            ],
            'role' => 'required|string|exists:roles,id',
            'branches' => 'nullable|array',
            'branches.*' => 'integer|exists:branches,id',
            'hidden' => 'nullable|boolean',
        ]);

        $role = Role::findOrFail($request->role);
        $this->guardSuperAdminRole($role);

        unset($userData['branches']);

        // Only super-admins may change the hidden flag; others keep it as-is.
        if ($this->viewerIsSuperAdmin()) {
            $userData['hidden'] = $request->boolean('hidden');
        } else {
            unset($userData['hidden']);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = 'storage/users/' . uniqid() . '.webp';

            $manager = new ImageManager(new Driver());
            $manager->read($image)
                ->scale(width: 450)
                ->encode(new AutoEncoder('webp', quality: 75))
                ->save($imagePath);

            $userData['image'] = $imagePath;

            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
        }

        $user->update($userData);

        $user->syncRoles([$role]);

        $user->branches()->sync($request->input('branches', []));

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(User $user)
    {
        if ($this->isConcealed($user)) {
            abort(403);
        }

        if($user->id == Auth::guard('api')->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكنك حذف حسابك'
            ], 403);
        }

        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Only a super-admin may assign the super_admin role to a user.
     */
    private function guardSuperAdminRole(Role $role): void
    {
        if ($role->name === $this->superAdminName() && ! $this->viewerIsSuperAdmin()) {
            abort(403);
        }
    }
}
