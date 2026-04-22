<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('forgot', [
            'email' => ['required', 'email', 'exists:users,email'],
            'new_password' => ['required', 'string', 'min:8', 'same:new_password_confirmation'],
            'new_password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->where('email', $validated['email'])->firstOrFail();
        $user->update([
            'password' => $validated['new_password'],
        ]);

        return redirect()
            ->route('welcome')
            ->with('forgot_success', 'Password updated successfully. You can now log in.')
            ->with('auth_screen', 'login');
    }
}
