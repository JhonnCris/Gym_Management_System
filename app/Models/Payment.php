<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

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
}
