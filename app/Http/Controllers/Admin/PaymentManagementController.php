<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'All');
        $method = $request->query('method', 'All');

        $payments = DB::table('member_payment_summary as summary')
            ->join('payments', 'payments.payment_id', '=', 'summary.payment_id')
            ->select('summary.*', 'payments.gcash_image_path')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('summary.payment_id', 'like', "%{$search}%")
                        ->orWhere('summary.member_id', 'like', "%{$search}%")
                        ->orWhere('summary.payment_method', 'like', "%{$search}%")
                        ->orWhere('summary.member_name', 'like', "%{$search}%")
                        ->orWhere('summary.member_email', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'All', fn ($q) => $q->where('summary.payment_status', $status))
            ->when($method !== 'All', fn ($q) => $q->where('summary.payment_method', $method))
            ->latest('summary.payment_date')
            ->paginate(10)
            ->through(function (object $payment): array {
                $paymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
                $transactionId = 'TXN'.$paymentDate?->format('YmdHis').$payment->payment_id;

                return [
                    'payment_id' => $payment->payment_id,
                    'transaction_id' => $transactionId,
                    'member' => $payment->member_name ?? ('Member #'.$payment->member_id),
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'type' => $payment->requested_membership_type ? 'Plan update' : 'Membership',
                    'requested_membership_type' => $payment->requested_membership_type,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'gcash_number' => $payment->gcash_number,
                    'gcash_image_url' => $payment->gcash_image_path ? route('admin.payments.proof', ['payment' => $payment->payment_id]) : null,
                    'status' => $payment->payment_status,
                    'date' => $paymentDate?->format('Y-m-d H:i') ?? '',
                    'can_approve' => $payment->payment_status === 'Pending' && !(
                        $payment->payment_method === 'GCash'
                        && (empty($payment->gcash_number) || empty($payment->gcash_image_path))
                    ),
                ];
            });

        $stats = [
            'total_revenue' => Payment::getTotalPaidAmount(),
            'pending_count' => Payment::getPendingCount(),
            'pending_total' => (float) DB::table('member_payment_summary')->where('payment_status', 'Pending')->sum('amount'),
            'failed_count' => (int) DB::table('member_payment_summary')->where('payment_status', 'Failed')->count(),
        ];

        $methods = DB::table('payment_methods_view')
            ->orderBy('payment_method')
            ->pluck('payment_method');

        return response()->json([
            'payments' => $payments,
            'stats' => $stats,
            'methods' => $methods,
        ]);
    }

    public function approve(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'Pending') {
            return response()->json(['message' => 'Only pending payments can be approved.'], 422);
        }

        if ($payment->payment_method === 'GCash' && (empty($payment->gcash_number) || empty($payment->gcash_image_path))) {
            return response()->json(['message' => 'GCash payments require the member account and proof image before review.'], 422);
        }

        DB::transaction(function () use ($request, $payment): void {
            $plan = $payment->requestedMembershipPlan;
            $member = Member::query()->findOrFail($payment->member_id);

            if ($plan) {
                $startDate = optional($member->expiry_date)->isFuture()
                    ? $member->expiry_date->copy()
                    : now();

                $member->update([
                    'membership_plan_id' => $plan->mem_plan_id,
                    'membership_type' => $plan->name,
                    'status' => 'Active',
                    'expiry_date' => $startDate->copy()->addMonths($plan->duration_months)->toDateString(),
                ]);
            }

            $payment->update([
                'status' => 'Paid',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $request->user()?->id,
            ]);
        });

        return response()->json(['message' => 'Payment approved and membership updated.']);
    }

    public function proof(Payment $payment)
    {
        if (!$payment->gcash_image_path) {
            abort(404, 'Proof image is not available.');
        }

        $path = Storage::disk('public')->path($payment->gcash_image_path);

        if (!Storage::disk('public')->exists($payment->gcash_image_path) || !is_file($path)) {
            abort(404, 'Proof image not found.');
        }

        return response()->file($path);
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'Pending') {
            return response()->json(['message' => 'Only pending payments can be rejected.'], 422);
        }

        if ($payment->payment_method === 'GCash' && (empty($payment->gcash_number) || empty($payment->gcash_image_path))) {
            return response()->json(['message' => 'GCash payments require the member account and proof image before review.'], 422);
        }

        $payment->update([
            'status' => 'Failed',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()?->id,
        ]);

        return response()->json(['message' => 'Payment request rejected.']);
    }
}
