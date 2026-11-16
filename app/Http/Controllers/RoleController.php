<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RoleController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(20);
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        return view('roles.index', compact('users', 'roles', 'permissions'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // Assign user role by default
            $user->assignRole('user');

            return redirect()->back()->with('success', 'User created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating user: ' . $e->getMessage());
        }
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->back()->with('success', 'Role assigned successfully!');
    }

    public function revokeRole(User $user, Role $role)
    {
        // Prevent admin from being deleted
        if ($user->hasRole('admin') && $user->email === 'admin@domain.com') {
            return redirect()->back()->with('error', 'Admin user cannot be deleted!');
        }

        $user->removeRole($role);

        return redirect()->back()->with('success', 'Role revoked successfully!');
    }

    public function destroy(User $user)
    {
        // Prevent admin from being deleted
        if ($user->hasRole('admin') && $user->email === 'admin@domain.com') {
            return redirect()->back()->with('error', 'Admin user cannot be deleted!');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully!');
    }
}
