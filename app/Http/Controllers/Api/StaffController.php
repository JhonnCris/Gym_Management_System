<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Staff::query()
                ->with(['user', 'classes'])
                ->latest('staff_id')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:staff,user_id'],
            'role' => ['required', Rule::in(['Trainer', 'Receptionist', 'Manager'])],
            'specialization' => ['nullable', 'string', 'max:100'],
        ]);

        $staff = Staff::create($validated);

        return response()->json($staff->load(['user', 'classes']), 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        return response()->json($staff->load(['user', 'classes']));
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', 'exists:users,id', Rule::unique('staff', 'user_id')->ignore($staff->staff_id, 'staff_id')],
            'role' => ['sometimes', 'required', Rule::in(['Trainer', 'Receptionist', 'Manager'])],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $staff->update($validated);

        return response()->json($staff->fresh()->load(['user', 'classes']));
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();

        return response()->json(status: 204);
    }
}
