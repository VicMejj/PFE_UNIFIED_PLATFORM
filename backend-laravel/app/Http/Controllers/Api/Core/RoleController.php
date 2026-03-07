<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends ApiController
{
    /**
     * Display a listing of roles
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::with('permissions')->paginate(15);
        return $this->successResponse($roles, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->attach($request->permissions);
        }

        return $this->successResponse($role, 'Role created successfully', 201);
    }

    /**
     * Display the specified role
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return $this->successResponse($role, 'Role retrieved successfully');
    }

    /**
     * Update the specified role
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|unique:roles,name,' . $id,
            'display_name' => 'nullable|string',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $role->update($request->only(['name', 'display_name', 'description']));

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $this->successResponse($role, 'Role updated successfully');
    }

    /**
     * Remove the specified role
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent deletion of system roles
        if (in_array($role->name, ['admin', 'user'])) {
            return $this->errorResponse('Cannot delete system roles', 403);
        }

        $role->delete();
        return $this->successResponse(null, 'Role deleted successfully');
    }

    /**
     * Assign a role to a user
     * 
     * Only ADMIN can assign roles to other users
     */
    public function assignRoleToUser(Request $request, $userId)
    {
        // Check authorization
        if (! auth()->user()->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can assign roles');
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = \App\Models\User::findOrFail($userId);

        try {
            $user->assignRole($request->role);

            return $this->successResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'assigned_role' => $request->role,
                'all_roles' => $user->getRoleNames(),
            ], 'Role assigned to user successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove a role from a user
     * 
     * Only ADMIN can remove roles
     */
    public function removeRoleFromUser(Request $request, $userId)
    {
        // Check authorization
        if (! auth()->user()->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can remove roles');
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = \App\Models\User::findOrFail($userId);

        try {
            $user->removeRole($request->role);

            return $this->successResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'removed_role' => $request->role,
                'remaining_roles' => $user->getRoleNames(),
            ], 'Role removed from user successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get user's roles and permissions
     */
    public function getUserRoles($userId)
    {
        $user = \App\Models\User::findOrFail($userId);

        return $this->successResponse([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ], 'User roles and permissions retrieved successfully');
    }

    /**
     * Get all users with their roles
     * 
     * Only ADMIN can view this
     */
    public function getAllUsersWithRoles()
    {
        // Check authorization
        if (! auth()->user()->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can view all user roles');
        }

        $users = \App\Models\User::with('roles', 'permissions')
            ->paginate(15)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions_count' => $user->permissions()->count(),
                ];
            });

        return $this->successResponse($users, 'All users with roles retrieved successfully');
    }

    /**
     * Get users by specific role
     * 
     * Only ADMIN can do this
     */
    public function getUsersByRole($roleName)
    {
        // Check authorization
        if (! auth()->user()->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can filter users by role');
        }

        // Verify role exists
        $role = Role::where('name', $roleName)->firstOrFail();

        $users = $role->users()
            ->select('id', 'name', 'email', 'created_at')
            ->paginate(15);

        return $this->successResponse([
            'role' => $roleName,
            'total_users' => $role->users()->count(),
            'users' => $users,
        ], "Users with '{$roleName}' role retrieved successfully");
    }

    /**
     * Sync (replace all) user's roles
     * 
     * Only ADMIN can do this
     */
    public function syncUserRoles(Request $request, $userId)
    {
        // Check authorization
        if (! auth()->user()->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can sync user roles');
        }

        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = \App\Models\User::findOrFail($userId);

        try {
            $user->syncRoles($request->roles);

            return $this->successResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getPermissionNames(),
            ], 'User roles synchronized successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
