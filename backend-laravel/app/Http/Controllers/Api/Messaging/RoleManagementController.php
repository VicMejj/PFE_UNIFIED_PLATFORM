<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    private function ensureAdmin(): ?JsonResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('admin')) {
            return response()->json([
                'error' => 'Only administrators can manage roles.',
            ], 403);
        }

        return null;
    }

    public function getAllRoles(): JsonResponse
    {
        if ($response = $this->ensureAdmin()) {
            return $response;
        }

        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ];
        });

        return response()->json([
            'success' => true,
            'roles' => $roles,
        ]);
    }

    public function getUserRole(int $userId): JsonResponse
    {
        if ($response = $this->ensureAdmin()) {
            return $response;
        }

        $user = User::findOrFail($userId);
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function updateUserRole(Request $request, int $userId): JsonResponse
    {
        if ($response = $this->ensureAdmin()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $targetUser = User::findOrFail($userId);
        $newRole = $request->role;

        if ($targetUser->id === Auth::id() && $targetUser->hasRole('admin') && $newRole !== 'admin') {
            return response()->json([
                'error' => 'You cannot remove your own admin access.',
            ], 403);
        }

        $targetUser->syncRoles([$newRole]);

        return response()->json([
            'success' => true,
            'message' => "User role updated to '{$newRole}'",
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'new_role' => $newRole,
        ]);
    }

    public function assignRole(Request $request, int $userId): JsonResponse
    {
        if ($response = $this->ensureAdmin()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $targetUser = User::findOrFail($userId);
        $roleName = $request->role;

        $targetUser->assignRole($roleName);

        return response()->json([
            'success' => true,
            'message' => "Role '{$roleName}' assigned to user",
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
            ],
            'roles' => $targetUser->getRoleNames(),
        ]);
    }

    public function removeRole(Request $request, int $userId): JsonResponse
    {
        if ($response = $this->ensureAdmin()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $targetUser = User::findOrFail($userId);
        $roleName = $request->role;

        if ($targetUser->id === Auth::id() && $roleName === 'admin' && $targetUser->hasRole('admin')) {
            return response()->json([
                'error' => 'You cannot remove your own admin access.',
            ], 403);
        }

        $targetUser->removeRole($roleName);

        return response()->json([
            'success' => true,
            'message' => "Role '{$roleName}' removed from user",
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
            ],
            'roles' => $targetUser->getRoleNames(),
        ]);
    }

    public function getUsersByRole(Request $request): JsonResponse
    {
        if ($response = $this->ensureAdmin()) {
            return $response;
        }

        $roleName = $request->query('role');
        
        if (!$roleName) {
            return response()->json([
                'error' => 'Role parameter is required'
            ], 422);
        }

        $users = User::role($roleName)->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'role' => $roleName,
            'users' => $users,
            'count' => $users->count(),
        ]);
    }
}
