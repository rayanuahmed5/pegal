<?php

namespace App\Http\Controllers;

use App\Domain\InvalidUser;
use App\Domain\UserAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// every signed-in user sees their own profile and can change their own password, nobody else's
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request, UserAccounts $accounts): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $accounts->changePassword($request->user(), $data['current_password'], $data['password']);
        } catch (InvalidUser $e) {
            return back()->withErrors(['current_password' => $e->getMessage()]);
        }

        // a fresh session id, so a session someone might have copied before the change stops working
        $request->session()->regenerate();

        return redirect()->route('profile.show')->with('status', 'Your password was changed.');
    }
}
