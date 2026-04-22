<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Booking::query()
                ->with(['member.user', 'gymClass'])
                ->latest('created_at')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,member_id'],
            'class_id' => ['required', 'exists:classes,class_id'],
            'status' => ['nullable', Rule::in(['Booked', 'Cancelled', 'Completed'])],
        ]);

        $alreadyBooked = Booking::query()
            ->where('member_id', $validated['member_id'])
            ->where('class_id', $validated['class_id'])
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'message' => 'This member already has a booking for this class.',
            ], 422);
        }

        $gymClass = GymClass::findOrFail($validated['class_id']);
        $bookedCount = $gymClass->bookings()
            ->where('status', 'Booked')
            ->count();

        if ($bookedCount >= $gymClass->max_slots) {
            return response()->json([
                'message' => 'No slots available for this class.',
            ], 422);
        }

        $member = Member::query()->findOrFail($validated['member_id']);
        $limit = $this->membershipBookingLimit($member->membership_type);

        if ($limit !== null) {
            $memberBookedCount = Booking::query()
                ->where('member_id', $member->member_id)
                ->where('status', 'Booked')
                ->whereHas('gymClass', fn ($query) => $query->where('schedule_time', '>=', now()))
                ->count();

            if ($memberBookedCount >= $limit) {
                return response()->json([
                    'message' => 'Your membership plan allows only '.$limit.' active booked class'.($limit === 1 ? '' : 'es').'.',
                ], 422);
            }
        }

        $booking = Booking::create($validated);

        return response()->json($booking->load(['member.user', 'gymClass']), 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['member.user', 'gymClass']));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Booked', 'Cancelled', 'Completed'])],
        ]);

        $booking->update($validated);

        return response()->json($booking->fresh()->load(['member.user', 'gymClass']));
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();

        return response()->json(status: 204);
    }

    private function membershipBookingLimit(?string $membershipType): ?int
    {
        return match ($membershipType) {
            'Basic' => 1,
            'Premium' => 10,
            'VIP' => null,
            default => 1,
        };
    }
}
