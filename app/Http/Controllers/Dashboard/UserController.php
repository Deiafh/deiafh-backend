<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
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
        ->orderBy($orderBy, $orderDirection)->paginate($size);

        $users->getCollection()->transform(function ($user) {
            return [
                "id" => $user->id,
                "username" => $user->username,
                "name" => $user->name,
                "image" => $user->image_url,
                "role" => $user->getRoleNames()->first() ?? 'لا يوجد دور',
            ];
        });

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json([
            "id" => $user->id,
            "username" => $user->username,
            "name" => $user->name,
            "image" => $user->image_url,
            "role" => $user->roles()->first()->id,
        ]);
    }

    public function store(Request $request) {
        $userData = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:'. (4 * 1024 * 1024),
            'name' => 'required|string|min:2|max:25',
            'username' => 'required|string|min:5|max:18|unique:users',
            'password' => [
                'required',
                'string',
                Password::min(9)->max(35)->letters()->numbers()
            ],
            'role' => 'required|string|exists:roles,id',
        ]);

        $image = $request->file('image');
        $imagePath = 'storage/users/' . uniqid() . '.webp';

        $manager = new ImageManager(new Driver());
        $manager->read($image)
            ->scale(width: 450)
            ->encode(new AutoEncoder('webp', quality: 75))
            ->save($imagePath);

        $userData['image'] = $imagePath;

        $user = User::create($userData);

        $user->assignRole(Role::findOrFail($request->role));

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
            ]
        ], 201);
    }

    public function update(Request $request, User $user) {
        $userData = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'. (4 * 1024 * 1024),
            'name' => 'required|string|min:2|max:25',
            'username' => 'required|string|min:5|max:18|unique:users,id,' . $user->id,
            'password' => [
                'nullable',
                'string',
                Password::min(9)->max(35)->letters()->numbers()
            ],
            'role' => 'required|string|exists:roles,id',
        ]);

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

        $user->syncRoles([Role::findOrFail($request->role)]);

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(User $user)
    {
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
}
