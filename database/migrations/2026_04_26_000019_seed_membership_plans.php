<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed the three membership plans from the hardcoded catalog
        DB::table('membership_plans')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'Basic',
                'price' => 1500.00,
                'duration_months' => 1,
                'description' => 'Gym floor access for everyday workouts.',
                'features' => json_encode(['Open gym access', 'Standard locker use', '1 class booking at a time']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Premium',
                'price' => 2500.00,
                'duration_months' => 1,
                'description' => 'Best balance for regular training and classes.',
                'features' => json_encode(['Unlimited gym access', 'Priority class booking', 'Progress tracking support']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'VIP',
                'price' => 3000.00,
                'duration_months' => 1,
                'description' => 'Full-featured access for members who train often.',
                'features' => json_encode(['All Premium benefits', 'Top booking priority', 'Member-first support']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear membership plans
        DB::table('membership_plans')->truncate();
    }
};
