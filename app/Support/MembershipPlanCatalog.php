<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MembershipPlanCatalog
{
    public static function all(): array
    {
        $metadata = [
            'Basic' => [
                'price' => 1500.00,
                'duration_months' => 1,
                'description' => 'Gym floor access for everyday workouts.',
                'features' => ['Open gym access', 'Standard locker use', '1 class booking at a time'],
            ],
            'Premium' => [
                'price' => 2500.00,
                'duration_months' => 1,
                'description' => 'Best balance for regular training and classes.',
                'features' => ['Unlimited gym access', 'Priority class booking', 'Progress tracking support'],
            ],
            'VIP' => [
                'price' => 3000.00,
                'duration_months' => 1,
                'description' => 'Full-featured access for members who train often.',
                'features' => ['All Premium benefits', 'Top booking priority', 'Member-first support'],
            ],
        ];

        $membershipTypes = Member::query()
            ->select('membership_type')
            ->distinct()
            ->whereNotNull('membership_type')
            ->orderBy('membership_type')
            ->pluck('membership_type');

        $membershipTypes = collect(array_keys($metadata))
            ->merge($membershipTypes)
            ->filter()
            ->unique()
            ->values();

        return $membershipTypes->map(function (string $membershipType) use ($metadata): array {
            $defaults = $metadata[$membershipType] ?? [
                'price' => 0.00,
                'duration_months' => 1,
                'description' => 'Membership plan available in the system.',
                'features' => ['Membership access', 'Gym floor use', 'Member account support'],
            ];

            $priceFromPayments = Schema::hasColumn('payments', 'requested_membership_type')
                ? DB::selectOne('SELECT get_membership_plan_price(?) AS value', [$membershipType])?->value
                : null;

            $resolvedPrice = (float) $defaults['price'];

            if ($priceFromPayments !== null && (float) $priceFromPayments > 0) {
                $resolvedPrice = (float) $priceFromPayments;
            }

            return [
                'name' => $membershipType,
                'price' => $resolvedPrice,
                'duration_months' => $defaults['duration_months'],
                'description' => $defaults['description'],
                'features' => $defaults['features'],
            ];
        })->values()->all();
    }

    public static function find(string $planName): ?array
    {
        return collect(self::all())->firstWhere('name', $planName);
    }
}
