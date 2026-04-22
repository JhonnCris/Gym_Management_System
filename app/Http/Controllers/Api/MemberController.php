<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Member::query()
                ->with('user')
                ->latest('member_id')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:members,user_id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'membership_type' => ['required', 'string', 'max:50'],
            'join_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['Active', 'Expired', 'Cancelled'])],
        ]);

        $member = Member::create($validated);

        return response()->json($member->load('user'), 201);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member->load('user'));
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', 'exists:users,id', Rule::unique('members', 'user_id')->ignore($member->member_id, 'member_id')],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'membership_type' => ['sometimes', 'required', 'string', 'max:50'],
            'join_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['Active', 'Expired', 'Cancelled'])],
        ]);

        $member->update($validated);

        return response()->json($member->fresh()->load('user'));
    }

    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return response()->json(status: 204);
    }
}
