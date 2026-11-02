<?php

namespace App\Http\Controllers;

use App\Domain\InvalidUser;
use App\Domain\Role;
use App\Domain\UserAccounts;
use App\Domain\UserOverview;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// the administrator's dashboard and everything around accounts (the routes only let admins in)
class AdminController extends Controller
{
    public function dashboard(UserOverview $overview): View
    {
        return view('admin.dashboard', [
            'total' => $overview->total(),
            'byRole' => $overview->countByRole(),
            'rows' => $overview->rows(),
        ]);
    }

    public function create(): View
    {
        return view('admin.create-user', [
            'roles' => Role::cases(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, UserAccounts $accounts): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // unique, because the email is the sign-in name
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'supplier_id' => ['nullable', 'integer'],
            // the administrator hands this out, 8 characters is the least we accept
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = $accounts->create(
                $data['name'],
                $data['email'],
                Role::from($data['role']),
                isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                $data['password'],
            );
        } catch (InvalidUser $e) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['supplier_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', sprintf('%s was added as %s.', $user->name, $user->role->label()));
    }

    public function edit(User $user): View
    {
        return view('admin.edit-user', [
            'user' => $user,
            'roles' => Role::cases(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'bookings' => $user->bookings()->count(),
        ]);
    }

    public function update(Request $request, User $user, UserAccounts $accounts): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // still unique, but the person may keep their own email
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'supplier_id' => ['nullable', 'integer'],
        ]);

        try {
            $accounts->update(
                $user,
                $data['name'],
                $data['email'],
                Role::from($data['role']),
                isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                $request->user(),
            );
        } catch (InvalidUser $e) {
            return back()->withInput()->withErrors(['profile' => $e->getMessage()]);
        }

        return redirect()->route('admin.dashboard')->with('status', sprintf('%s was updated.', $user->name));
    }

    public function resetPassword(Request $request, User $user, UserAccounts $accounts): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $accounts->resetPassword($user, $data['password']);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', sprintf('The password of %s was reset. Tell them the new one in a safe way.', $user->name));
    }

    public function destroy(Request $request, User $user, UserAccounts $accounts): RedirectResponse
    {
        try {
            $accounts->delete($user, $request->user());
        } catch (InvalidUser $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()->route('admin.dashboard')->with('status', sprintf('%s was deleted.', $user->name));
    }
}
