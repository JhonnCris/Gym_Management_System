<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill members.membership_plan_id based on existing membership_type
        DB::statement('
            UPDATE members m
            INNER JOIN membership_plans mp ON m.membership_type = mp.name
            SET m.membership_plan_id = mp.mem_plan_id
            WHERE m.membership_plan_id IS NULL AND m.membership_type IS NOT NULL
        ');

        // Backfill payments.requested_membership_plan_id based on existing requested_membership_type
        if (Schema::hasColumn('payments', 'requested_membership_type')) {
            DB::statement('
                UPDATE payments p
                INNER JOIN membership_plans mp ON p.requested_membership_type = mp.name
                SET p.requested_membership_plan_id = mp.mem_plan_id
                WHERE p.requested_membership_plan_id IS NULL AND p.requested_membership_type IS NOT NULL
            ');
        }

        // Create initial maintenance log entries for equipment with issues
        // Find equipment that has status='Maintenance' or has equipment issues
        $equipmentWithIssues = DB::table('equipments')
            ->where('status', 'Maintenance')
            ->get();

        foreach ($equipmentWithIssues as $equipment) {
            DB::table('equipment_maintenance_logs')->insertOrIgnore([
                'equipment_id' => $equipment->equipment_id,
                'maintenance_type' => 'Inspection',
                'status' => 'In Progress',
                'description' => 'Equipment under maintenance - initial log entry',
                'performed_by' => null,
                'performed_date' => null,
                'next_scheduled_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear backfilled data
        DB::statement('UPDATE members SET membership_plan_id = NULL WHERE membership_plan_id IS NOT NULL');
        
        if (Schema::hasColumn('payments', 'requested_membership_plan_id')) {
            DB::statement('UPDATE payments SET requested_membership_plan_id = NULL WHERE requested_membership_plan_id IS NOT NULL');
        }

        DB::table('equipment_maintenance_logs')->truncate();
    }
};
