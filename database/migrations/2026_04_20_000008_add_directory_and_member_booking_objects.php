<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        return;

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS `get_member_booking_schedule`');
        DB::unprepared('DROP VIEW IF EXISTS `user_overview_view`');

        DB::unprepared(<<<'SQL'
            CREATE VIEW user_overview_view AS
            SELECT
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.role AS user_role,
                u.status AS user_status,
                u.created_at AS user_created_at,
                u.last_visit_at,
                m.member_id,
                m.membership_type,
                m.join_date,
                m.expiry_date,
                m.status AS member_status,
                s.staff_id,
                s.role AS staff_role,
                s.specialization
            FROM users u
            LEFT JOIN members m ON m.user_id = u.id
            LEFT JOIN staff s ON s.user_id = u.id
            WHERE u.deleted_at IS NULL
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_member_booking_schedule(IN member_id_param BIGINT)
            BEGIN
                SELECT
                    b.booking_id,
                    b.member_id,
                    b.class_id,
                    b.status AS booking_status,
                    c.class_name,
                    c.schedule_time,
                    c.max_slots,
                    c.bookings_count,
                    c.trainer_names
                FROM bookings b
                INNER JOIN classes_with_bookings_view c ON c.class_id = b.class_id
                WHERE b.member_id = member_id_param
                ORDER BY c.schedule_time DESC, b.booking_id DESC;
            END
        SQL);
    }

    public function down(): void
    {
        return;

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP PROCEDURE IF EXISTS `get_member_booking_schedule`');
        DB::unprepared('DROP VIEW IF EXISTS `user_overview_view`');
    }
};
