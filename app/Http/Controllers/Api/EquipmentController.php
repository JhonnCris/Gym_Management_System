<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Equipment::query()
                ->with('classes')
                ->latest('equipment_id')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['Available', 'In Use', 'Maintenance'])],
            'condition_status' => ['nullable', Rule::in(['Good', 'Damaged', 'Under Repair'])],
            'last_maintenance_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $equipment = Equipment::create($validated);

        return response()->json($equipment->load('classes'), 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return response()->json($equipment->load('classes'));
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['Available', 'In Use', 'Maintenance'])],
            'condition_status' => ['sometimes', 'required', Rule::in(['Good', 'Damaged', 'Under Repair'])],
            'last_maintenance_date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $equipment->update($validated);

        return response()->json($equipment->fresh()->load('classes'));
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json(status: 204);
    }
}
