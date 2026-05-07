<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'member_id',
        'requested_membership_plan_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'gcash_number',
        'gcash_image_path',
        'requested_membership_type',
        'reviewed_at',
        'reviewed_by_user_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function requestedMembershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'requested_membership_plan_id', 'mem_plan_id');
    }

    /**
     * Get pending payments view (replaces pending_payments_view)
     */
    public function scopePendingPayments(Builder $query)
    {
        return $query->join('members', 'payments.member_id', '=', 'members.member_id')
            ->join('users', 'members.user_id', '=', 'users.id')
            ->where('payments.status', 'Pending')
            ->select([
                'payments.payment_id',
                'payments.member_id',
                'payments.amount',
                'payments.payment_date',
                'payments.payment_method',
                'payments.status',
                'payments.reference_number',
                'members.membership_type',
                'users.full_name',
                'users.email',
            ])
            ->orderBy('payments.payment_date', 'desc')
            ->limit(8);
    }

    /**
     * Get total paid amount (replaces get_total_paid_amount function)
     */
    public static function getTotalPaidAmount(): float
    {
        return self::where('status', 'Paid')->sum('amount') ?? 0;
    }

    /**
     * Get pending count (replaces get_pending_count function)
     */
    public static function getPendingCount(): int
    {
        return self::where('status', 'Pending')->count();
    }
}
