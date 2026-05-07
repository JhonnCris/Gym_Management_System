<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Support\MembershipPlanCatalog;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberPageController extends Controller
{
    public function dashboard(): View
    {
        [$user, $member] = $this->memberContext();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $bookingRows = $this->memberBookingSchedule($member->member_id);
        $upcomingBookings = $bookingRows->filter(fn ($booking) => optional($booking->schedule_time)?->gte(now()))->take(3)->values();
        $paymentRows = $this->memberPayments($member->member_id);
        $latestPayment = $paymentRows->first();
        $paidPayments = $paymentRows->where('status', 'Paid');
        $attendanceRows = $this->memberAttendances($member->member_id);
        $completedVisits = $attendanceRows->filter(fn ($attendance) => $attendance->check_in_time !== null);

        $weeklyVisits = collect(range(0, 6))->map(function (int $offset) use ($attendanceRows, $weekStart) {
            $day = $weekStart->copy()->addDays($offset);

            return [
                'label' => $day->format('D'),
                'count' => $attendanceRows
                    ->filter(fn ($attendance) => optional($attendance->check_in_time)?->isSameDay($day))
                    ->count(),
            ];
        });

        return view('member.dashboard', [
            'user' => $user,
            'member' => $member,
            'stats' => [
                'membership' => $member->membership_type ?? 'Standard',
                'visits_this_week' => $completedVisits
                    ->filter(fn ($attendance) => optional($attendance->check_in_time)?->between($weekStart, $weekEnd))
                    ->count(),
                'booked_classes' => $bookingRows->filter(fn ($booking) => optional($booking->schedule_time)?->gte(now()) && $booking->status === 'Booked')->count(),
                'next_renewal' => optional($member->expiry_date)?->format('M d, Y') ?? 'Not set',
            ],
            'summary' => [
                'total_visits' => $completedVisits->count(),
                'completed_payments' => $paidPayments->count(),
                'paid_total' => (float) $paidPayments->sum('amount'),
            ],
            'weeklyVisits' => $weeklyVisits,
            'weeklyVisitMax' => max(1, (int) $weeklyVisits->max('count')),
            'upcomingBookings' => $upcomingBookings,
            'latestPayment' => $latestPayment,
        ]);
    }

    public function profile(): View
    {
        [$user, $member] = $this->memberContext();

        $latestAttendance = $this->memberAttendances($member->member_id)->first();
        $successfulPayments = $this->memberPayments($member->member_id)->where('status', 'Paid')->count();
        $bookingsCount = $this->memberBookingSchedule($member->member_id)
            ->where('status', 'Booked')
            ->where(fn ($booking) => optional($booking->schedule_time)?->gte(now()))
            ->count();

        return view('member.profile', [
            'user' => $user,
            'member' => $member,
            'latestAttendance' => $latestAttendance,
            'successfulPayments' => $successfulPayments,
            'bookingsCount' => $bookingsCount,
        ]);
    }

    public function classes(): View
    {
        [$user, $member] = $this->memberContext();

        $now = now();
        $memberBookings = $this->memberBookingSchedule($member->member_id);
        $activeBookedClassIds = $memberBookings
            ->filter(fn ($booking) => $booking->status === 'Booked' && optional($booking->schedule_time)?->gte($now))
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $upcomingBookedClasses = $memberBookings
            ->filter(fn ($booking) => optional($booking->schedule_time)?->gte($now) && $booking->status === 'Booked')
            ->values();
        $bookingHistory = $memberBookings
            ->filter(fn ($booking) => optional($booking->schedule_time)?->lt($now) || $booking->status !== 'Booked')
            ->values();

        $availableClasses = GymClass::withBookings()
            ->where('schedule_time', '>=', $now)
            ->havingRaw('COUNT(DISTINCT class_trainer.staff_id) > 0')
            ->orderBy('schedule_time')
            ->get()
            ->map(function (object $class): object {
                $class->schedule_time = $class->schedule_time ? Carbon::parse($class->schedule_time) : null;
                $class->bookings_count = (int) ($class->bookings_count ?? 0);

                return $class;
            })
            ->filter(function (object $class) use ($activeBookedClassIds): bool {
                return ! in_array((int) $class->class_id, $activeBookedClassIds, true)
                    && $class->bookings_count < $class->max_slots;
            })
            ->values();

        $paginatedAvailableClasses = $this->paginateCollection($availableClasses, 12, 'available_page');
        $paginatedBookingHistory = $this->paginateCollection($bookingHistory, 10, 'history_page');

        $bookingLimit = $this->membershipBookingLimit($member->membership_type);
        $hasReachedBookingLimit = $bookingLimit !== null && $upcomingBookedClasses->count() >= $bookingLimit;

        return view('member.classes', [
            'user' => $user,
            'member' => $member,
            'bookedClassIds' => $activeBookedClassIds,
            'bookedClasses' => $upcomingBookedClasses,
            'bookingHistory' => $paginatedBookingHistory,
            'availableClasses' => $paginatedAvailableClasses,
            'bookingLimit' => $bookingLimit,
            'hasReachedBookingLimit' => $hasReachedBookingLimit,
            'classSummary' => [
                'booked' => $upcomingBookedClasses->count(),
                'available' => $availableClasses->count(),
                'nextClass' => optional($upcomingBookedClasses->first()?->schedule_time)?->format('M d, h:i A') ?? 'No upcoming booking',
                'history' => $bookingHistory->count(),
            ],
        ]);
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

    public function payments(): View
    {
        [$user, $member] = $this->memberContext();

        $allPayments = $this->memberPayments($member->member_id);
        $latestPaid = $allPayments->firstWhere('status', 'Paid') ?: $allPayments->first();
        $paidPayments = $allPayments->where('status', 'Paid');
        $paymentMethods = DB::table('payment_methods_view')
            ->orderBy('payment_method')
            ->pluck('payment_method')
            ->filter(fn ($method) => in_array($method, ['GCash', 'Card'], true))
            ->values()
            ->all();

        return view('member.payments', [
            'user' => $user,
            'member' => $member,
            'payments' => $this->paginateCollection($allPayments, 15, 'payments_page'),
            'latestPaid' => $latestPaid,
            'plans' => MembershipPlanCatalog::all(),
            'paymentMethods' => $paymentMethods ?: ['GCash', 'Card'],
            'paymentSummary' => [
                'paid_count' => $paidPayments->count(),
                'paid_total' => (float) $paidPayments->sum('amount'),
                'last_payment_date' => optional($latestPaid?->payment_date)?->format('M d, Y') ?? 'No payment yet',
            ],
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        [, $member] = $this->memberContext();

        // Check if current membership is still active
        if ($member->expiry_date && $member->expiry_date->gte(now())) {
            return response()->json([
                'message' => 'You cannot change your membership plan until your current plan expires.',
            ], 422);
        }

        $validated = $request->validate([
            'membership_type' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'in:GCash,Card'],
            'reference_number' => ['required', 'string', 'max:60'],
            'gcash_number' => ['required_if:payment_method,GCash', 'nullable', 'string', 'max:20'],
            'gcash_proof_image' => ['required_if:payment_method,GCash', 'nullable', 'image', 'max:5120'],
            'card_name' => ['required_if:payment_method,Card', 'nullable', 'string', 'max:120'],
            'card_last_four' => ['required_if:payment_method,Card', 'nullable', 'digits:4'],
            'card_network' => ['required_if:payment_method,Card', 'nullable', 'string', 'max:40'],
        ]);

        $selectedPlan = MembershipPlanCatalog::find($validated['membership_type']);

        abort_unless($selectedPlan, 422, 'Selected plan is not available.');

        $hasPending = Payment::query()
            ->where('member_id', $member->member_id)
            ->where('status', 'Pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'message' => 'You already have a payment request waiting for admin approval.',
            ], 422);
        }

        DB::transaction(function () use ($member, $validated, $selectedPlan, $request): void {
            $gcashImagePath = null;
            if ($request->hasFile('gcash_proof_image')) {
                $gcashImagePath = $request->file('gcash_proof_image')->store('payments/gcash', 'public');
            }

            Payment::create([
                'member_id' => $member->member_id,
                'requested_membership_plan_id' => $selectedPlan->mem_plan_id,
                'amount' => $selectedPlan->price,
                'payment_date' => now(),
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'gcash_number' => $validated['gcash_number'] ?? null,
                'gcash_image_path' => $gcashImagePath,
                'requested_membership_type' => $selectedPlan->name,
                'status' => 'Pending',
            ]);
        });

        $latestPayment = Payment::query()
            ->where('member_id', $member->member_id)
            ->latest('payment_date')
            ->first();

        return response()->json([
            'message' => 'Payment request submitted. An admin must approve it before your plan is updated.',
            'payment' => [
                'amount' => number_format((float) $latestPayment?->amount, 2),
                'method' => $latestPayment?->payment_method,
                'date' => optional($latestPayment?->payment_date)?->format('M d, Y h:i A'),
                'status' => $latestPayment?->status,
                'requested_membership_type' => $latestPayment?->requested_membership_type,
            ],
        ]);
    }

    /**
     * @return array{0: User, 1: Member}
     */
    private function memberContext(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(404);
        }

        $member = $user->member()->with('user')->first();

        abort_unless($member, 404);

        return [$user, $member];
    }

    private function memberPayments(int $memberId)
    {
        return collect(DB::select('CALL get_member_payments(?)', [$memberId]))
            ->map(function (object $payment): object {
                $payment->status = $payment->payment_status;
                $payment->payment_date = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
                $payment->reviewed_at = $payment->reviewed_at ? Carbon::parse($payment->reviewed_at) : null;

                return $payment;
            });
    }

    private function memberAttendances(int $memberId)
    {
        return collect(DB::select('CALL get_member_attendances(?)', [$memberId]))
            ->map(function (object $attendance): object {
                $attendance->check_in_time = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
                $attendance->check_out_time = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

                return $attendance;
            })
            ->sortByDesc('check_in_time')
            ->values();
    }

    private function memberBookingSchedule(int $memberId)
    {
        return Booking::getMemberBookingSchedule($memberId)
            ->map(function (object $booking): object {
                $booking->status = $booking->booking_status;
                $booking->schedule_time = $booking->schedule_time ? Carbon::parse($booking->schedule_time) : null;

                return $booking;
            })
            ->values();
    }
    private function storePayment(Request $request)
{
    $member = DB::table('members')
        ->where('member_id', $request->member_id)
        ->first();

    if ($member->expiry_date && Carbon::parse($member->expiry_date)->gte(now())) {
        // allow only advance (no same-day overlap)
        $startDate = Carbon::parse($member->expiry_date)->addDay();
    } else {
        // expired → start today
        $startDate = now();
    }

    DB::table('payments')->insert([
        'member_id' => $member->member_id,
        'requested_membership_plan_id' => $request->plan_id,
        'requested_membership_type' => $request->membership_type,
        'amount' => $request->amount,
        'payment_date' => now(),
        'payment_method' => $request->method,
        'status' => 'Pending',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return "Payment submitted. Waiting for approval.";
}
}

    
