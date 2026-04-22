<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            User::query()
                ->with(['member', 'staff'])
                ->latest()
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['Admin', 'Staff', 'Member'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($validated);

        return response()->json($user->load(['member', 'staff']), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load(['member', 'staff']));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', 'required', Rule::in(['Admin', 'Staff', 'Member'])],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
        ]);

        $user->update($validated);

        return response()->json($user->fresh()->load(['member', 'staff']));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(status: 204);
    }
}
