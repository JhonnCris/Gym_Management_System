<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Attendance::query()
                ->with(['member.user', 'gymClass'])
                ->latest('attendance_id')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,member_id'],
            'class_id' => ['required', 'exists:classes,class_id'],
            'check_in_time' => ['required', 'date'],
            'check_out_time' => ['nullable', 'date', 'after_or_equal:check_in_time'],
            'status' => ['nullable', Rule::in(['Present', 'Absent'])],
        ]);

        $attendance = Attendance::create($validated);

        return response()->json($attendance->load(['member.user', 'gymClass']), 201);
    }

    public function show(Attendance $attendance): JsonResponse
    {
        return response()->json($attendance->load(['member.user', 'gymClass']));
    }

    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'check_in_time' => ['sometimes', 'required', 'date'],
            'check_out_time' => ['sometimes', 'nullable', 'date', 'after_or_equal:check_in_time'],
            'status' => ['sometimes', 'required', Rule::in(['Present', 'Absent'])],
        ]);

        $attendance->update($validated);

        return response()->json($attendance->fresh()->load(['member.user', 'gymClass']));
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return response()->json(status: 204);
    }
}
