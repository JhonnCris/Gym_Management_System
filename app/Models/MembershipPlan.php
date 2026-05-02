<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';

    protected $primaryKey = 'mem_plan_id';

    protected $fillable = [
        'name',
        'price',
        'duration_months',
        'description',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'duration_months' => 'integer',
    ];

    /**
     * Get all members with this plan
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'membership_plan_id', 'mem_plan_id');
    }

    /**
     * Get all payment requests for this plan
     */
    public function requestedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'requested_membership_plan_id', 'mem_plan_id');
    }
}
