<?php

namespace App\Support;

use App\Models\MembershipPlan;
use Illuminate\Support\Collection;

class MembershipPlanCatalog
{
    /**
     * Get all membership plans from the database.
     *
     * @return Collection Collection of MembershipPlan models
     */
    public static function all(): Collection
    {
        return MembershipPlan::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a membership plan by name.
     *
     * @param  string  $name  Membership plan name (Basic, Premium, VIP, etc.)
     * @return MembershipPlan|null The membership plan or null if not found
     */
    public static function find(string $name): ?MembershipPlan
    {
        return MembershipPlan::query()
            ->where('name', $name)
            ->first();
    }
}
