<?php

namespace App\Http\Controllers;

use App\Domain\InvalidUser;
use App\Domain\Role;
use App\Domain\UserAccounts;
use App\Domain\UserOverview;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// the administrator's dashboard and the form for new users (the routes only let admins in)
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
}
