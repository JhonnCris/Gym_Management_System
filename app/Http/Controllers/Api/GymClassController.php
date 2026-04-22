<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GymClassController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            GymClass::query()
                ->with(['trainers', 'equipments'])
                ->orderBy('schedule_time')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:100'],
            'schedule_time' => ['required', 'date'],
            'max_slots' => ['required', 'integer', 'min:1'],
            'trainer_ids' => ['nullable', 'array'],
            'trainer_ids.*' => ['integer', 'distinct', 'exists:staff,staff_id'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['integer', 'distinct', 'exists:equipments,equipment_id'],
        ]);

        $gymClass = GymClass::create($validated);

        $gymClass->trainers()->sync($validated['trainer_ids'] ?? []);
        $gymClass->equipments()->sync($validated['equipment_ids'] ?? []);

        return response()->json($gymClass->load(['trainers', 'equipments']), 201);
    }

    public function show(GymClass $gymClass): JsonResponse
    {
        return response()->json($gymClass->load(['trainers', 'equipments']));
    }

    public function update(Request $request, GymClass $gymClass): JsonResponse
    {
        $validated = $request->validate([
            'class_name' => ['sometimes', 'required', 'string', 'max:100'],
            'schedule_time' => ['sometimes', 'required', 'date'],
            'max_slots' => ['sometimes', 'required', 'integer', 'min:1'],
            'trainer_ids' => ['sometimes', 'array'],
            'trainer_ids.*' => ['integer', 'distinct', 'exists:staff,staff_id'],
            'equipment_ids' => ['sometimes', 'array'],
            'equipment_ids.*' => ['integer', 'distinct', 'exists:equipments,equipment_id'],
        ]);

        $gymClass->update($validated);

        if (array_key_exists('trainer_ids', $validated)) {
            $gymClass->trainers()->sync($validated['trainer_ids']);
        }

        if (array_key_exists('equipment_ids', $validated)) {
            $gymClass->equipments()->sync($validated['equipment_ids']);
        }

        return response()->json($gymClass->fresh()->load(['trainers', 'equipments']));
    }

    public function destroy(GymClass $gymClass): JsonResponse
    {
        $gymClass->delete();

        return response()->json(status: 204);
    }
}
