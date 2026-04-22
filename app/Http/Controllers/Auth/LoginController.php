<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $credentials['identity'], 'password' => $credentials['password']])) {
            return back()
                ->withErrors(['identity' => 'Invalid email or password.'])
                ->withInput($request->only('identity'));
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->update(['last_visit_at' => now()]);

        if ($user->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'Staff') {
            return redirect()->route('staff.dashboard');
        }

        if ($user->role === 'Member') {
            return redirect()->route('member.dashboard');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('welcome')
            ->withErrors(['identity' => 'This account does not have access to a supported module.']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }
}
