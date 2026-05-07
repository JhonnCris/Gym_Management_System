<?php

use App\Support\ManagedSqlFunctions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ([
            'failed_payments_recent_view',
            'payment_methods_view',
            'equipment_with_classes_view',
            'classes_with_bookings_view',
            'attendance_recent_view',
            'member_bookings_view',
            'membership_distribution_view',
            'pending_payments_view',
            'member_payment_summary',
            'user_overview_view',
        ] as $view) {
            DB::unprepared("DROP VIEW IF EXISTS `{$view}`");
        }

        foreach ([
            'cleanup_demo_data',
            'create_member_payment',
            'approve_payment',
            'get_members_joined_between',
            'get_attendances_by_date',
            'get_payments_by_status',
            'get_member_bookings',
            'get_member_booking_schedule',
            'get_member_attendances',
            'get_member_payments',
        ] as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `{$procedure}`");
        }

        foreach ([
            'get_membership_plan_price',
            'get_month_revenue',
            'get_equipment_issues_count',
            'get_classes_today_count',
            'get_week_attendance_count',
            'get_currently_in_count',
            'get_today_unique_members_count',
            'get_today_attendance_count',
            'get_total_attendances',
            'get_total_members',
            'get_pending_count',
            'get_total_paid_amount',
        ] as $function) {
            ManagedSqlFunctions::run("DROP FUNCTION IF EXISTS `{$function}`", "drop function {$function}");
        }
    }

    public function down(): void
    {
        // Intentionally left blank: table-only mode should not recreate DB objects.
    }
};
