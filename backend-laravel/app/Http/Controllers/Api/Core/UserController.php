<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiController
{
    /**
     * Display a listing of users
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check authorization - ensure roles are loaded
        $user = auth()->user()->load('roles');
        if (! $user->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can view all users');
        }

        $query = User::query();

        if ($request->has('role')) {
            $role = $request->role;
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $users = $query->paginate($request->get('per_page', 15));

        return $this->successResponse($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'type' => 'sometimes|string|max:50',
            'avatar' => 'sometimes|string|max:255',
            'lang' => 'sometimes|string|max:10',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->input('type', 'user'),
            'avatar' => $request->input('avatar', 'avatars/default.png'),
            'lang' => $request->input('lang', 'en'),
            'created_by' => auth()->id() ?? 'system',
        ]);

        if ($request->has('role_id')) {
            $user->roles()->attach($request->role_id);
        } else {
            $user->assignDefaultRole();
        }

        return $this->successResponse($user, 'User created successfully', 201);
    }

    /**
     * Display the specified user
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Check authorization - ensure roles are loaded
        $user = auth()->user()->load('roles');
        if (! $user->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can view user details');
        }

        $user = User::with('roles', 'permissions')->findOrFail($id);
        return $this->successResponse($user, 'User retrieved successfully');
    }

    /**
     * Update the specified user
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Check authorization - ensure roles are loaded
        $user = auth()->user()->load('roles');
        if (! $user->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can update users');
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return $this->successResponse($user, 'User updated successfully');
    }

    /**
     * Remove the specified user
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Check authorization - ensure roles are loaded
        $user = auth()->user()->load('roles');
        if (! $user->hasRole('admin')) {
            return $this->forbiddenResponse('Only administrators can delete users');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }

    protected function canManageUsers()
    {
        $user = auth()->user()->load('roles');
        return $user->hasAnyRole(['admin', 'rh', 'manager']);
    }

    public function suspend($id)
    {
        if (! $this->canManageUsers()) {
            return $this->forbiddenResponse('Only managers, HR, or administrators can suspend users');
        }

        $user = User::findOrFail($id);
        $user->is_disable = true;
        $user->is_active = false;
        $user->active_status = false;
        $user->save();

        $user->employees()->update(['is_active' => false]);

        return $this->successResponse($user, 'User suspended successfully');
    }

    public function ban($id)
    {
        if (! $this->canManageUsers()) {
            return $this->forbiddenResponse('Only managers, HR, or administrators can ban users');
        }

        $user = User::findOrFail($id);
        $user->employees()->update(['is_active' => false]);

        // Permanently delete the user and all associated data
        $user->delete();

        return $this->successResponse(null, 'User banned and permanently deleted successfully');
    }

    public function activate($id)
    {
        if (! $this->canManageUsers()) {
            return $this->forbiddenResponse('Only managers, HR, or administrators can activate users');
        }

        $user = User::findOrFail($id);
        $user->is_disable = false;
        $user->is_active = true;
        $user->active_status = true;
        $user->save();

        $user->employees()->update(['is_active' => true]);

        return $this->successResponse($user, 'User activated successfully');
    }

    public function updateStatus(Request $request, $id)
    {
        if (! $this->canManageUsers()) {
            return $this->forbiddenResponse('Only managers, HR, or administrators can update user status');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,suspended,banned',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $user = User::findOrFail($id);
        $status = $request->input('status');

        $user->active_status = $status !== 'active';
        $user->is_disable = $status !== 'active';
        $user->is_active = $status === 'active' ? 1 : 0;
        $user->save();

        return $this->successResponse($user, sprintf('User status set to %s successfully', $status));
    }
}
