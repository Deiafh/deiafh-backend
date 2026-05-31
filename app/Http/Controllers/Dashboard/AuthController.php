<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
            'remember' => ['nullable', 'boolean']
        ]);

        $credentials = [
            'username' => $validated['username'],
            'password' => $validated['password']
        ];

        // remember me expiration
        if ($validated['remember'] ?? false) {

            config([
                'jwt.ttl' => 60 * 24 * 30
            ]);

        }

        if (!$token = Auth::guard('api')->attempt($credentials)) {

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);

        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(
            $this->getUserData(Auth::guard('api')->user())
        );
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(
            Auth::guard('api')->refresh()
        );
    }

    protected function respondWithToken($token)
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'access_token' => $token,
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,

            'user' => $this->getUserData($user)
        ]);
    }

    private function getUserData($user)
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'image' => $user->image_url,
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ];
    }
}
