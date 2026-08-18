<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::orderByDesc('created_at')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id'  => 'required|in:' . User::ROLE_ADMIN . ',' . User::ROLE_USER,
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id'  => $validated['role_id'],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function toggleBlock(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot block your own account.');
        }

        $user->is_blocked = ! $user->is_blocked;
        $user->save();

        if ($user->is_blocked) {
            $this->forceLogoutUser($user);

            return back()->with('success', "{$user->name} has been blocked and logged out.");
        }

        return back()->with('success', "{$user->name} has been unblocked.");
    }

    public function forceLogout(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot log yourself out from here.');
        }

        $this->forceLogoutUser($user);

        return back()->with('success', "{$user->name} has been logged out of all devices.");
    }

    private function forceLogoutUser(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();
    }
}
