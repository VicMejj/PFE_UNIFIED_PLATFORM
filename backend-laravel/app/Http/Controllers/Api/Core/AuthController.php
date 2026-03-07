<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    /**
     * Register a new user and return a JWT token
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => 'user',
            'avatar' => 'avatars/default.png',
            'lang' => 'en',
            'created_by' => 'system',
        ]);

        // Assign default role if it exists
        try {
            $user->assignDefaultRole();
        } catch (\Exception $e) {
            // Role might not exist in database yet
        }

        $token = JWTAuth::fromUser($user);

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
        ], 'User registered', 201);
    }

    /**
     * Attempt to log a user in and return token
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = auth()->user()->load('roles','permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'token' => $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    /**
     * Invalidate the token (logout)
     */
    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return $this->successResponse(null, 'Successfully logged out');
    }

    /**
     * Get the authenticated user
     */
    public function me()
    {
        $user = auth()->user()->load('roles','permissions');

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ]);
    }

    /**
     * Refresh a token
     */
    public function refresh()
    {
        return $this->successResponse([
            'token' => JWTAuth::refresh(),
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
