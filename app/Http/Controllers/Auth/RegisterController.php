<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('signup', [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:20'],
            'create_password' => ['required', 'string', 'min:8', 'same:confirm_password'],
            'confirm_password' => ['required', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'full_name' => trim($validated['first_name'].' '.$validated['last_name']),
                'email' => $validated['email'],
                'phone' => $validated['mobile'],
                'role' => 'Member',
                'status' => 'Active',
                'password' => $validated['create_password'],
            ]);

            Member::create([
                'user_id' => $user->id,
                'phone' => $validated['mobile'],
                'membership_type' => 'Basic',
                'join_date' => now()->toDateString(),
                'expiry_date' => now()->addMonth()->toDateString(),
                'status' => 'Active',
            ]);
        });

        return redirect()
            ->route('welcome')
            ->with('signup_success', 'Account created successfully. You can now log in.')
            ->with('auth_screen', 'login');
    }
}
