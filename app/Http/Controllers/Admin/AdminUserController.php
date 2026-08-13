<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminUserController extends Controller
{
    public function index(): InertiaResponse
    {
        $users = User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => Role::values(),
        ]);
    }

    /**
     * Replaces rather than adds -- see User::roleLevel()'s doc block: this
     * app's whole permission model assumes one role per user.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', ValidationRule::in(Role::values())],
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('status', 'Role updated.');
    }

    /**
     * There is no self-registration in this app -- accounts are provisioned
     * by an admin. Sets the password directly rather than emailing a reset
     * link: MAIL_MAILER is "log" in this environment (no real SMTP), so the
     * self-service forgot-password flow isn't actually reachable by staff.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', ValidationRule::in(Role::values())],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        return back()->with('status', 'User created.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => $data['password']]);

        return back()->with('status', 'Password reset.');
    }
}
