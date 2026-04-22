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
        // Create Views
        DB::statement("
            CREATE VIEW pending_payments_view AS
            SELECT 
                p.payment_id, 
                p.member_id, 
                p.amount, 
                p.payment_date, 
                p.payment_method, 
                p.status, 
                p.reference_number, 
                m.membership_type, 
                u.full_name, 
                u.email
            FROM payments p
            JOIN members m ON p.member_id = m.member_id
            JOIN users u ON m.user_id = u.id
            WHERE p.status = 'Pending'
            ORDER BY p.payment_date DESC
            LIMIT 8
        ");

        DB::statement("
            CREATE VIEW membership_distribution_view AS
            SELECT 
                membership_type, 
                COUNT(*) AS aggregate 
            FROM members 
            GROUP BY membership_type 
            ORDER BY aggregate DESC
        ");

        DB::statement("
            CREATE VIEW member_bookings_view AS
            SELECT 
                b.booking_id, 
                b.member_id, 
                b.class_id, 
                b.status AS booking_status, 
                c.class_name, 
                c.schedule_time, 
                c.max_slots, 
                ct.staff_id, 
                u.full_name AS trainer_name
            FROM bookings b
            JOIN classes c ON b.class_id = c.class_id
            LEFT JOIN class_trainers ct ON c.class_id = ct.class_id
            LEFT JOIN users u ON ct.staff_id = u.id
        ");

        // Create Functions
        DB::statement("
            CREATE FUNCTION get_total_paid_amount() RETURNS DECIMAL(10,2)
            BEGIN
                RETURN (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid');
            END
        ");

        DB::statement("
            CREATE FUNCTION get_pending_count() RETURNS INT
            BEGIN
                RETURN (SELECT COUNT(*) FROM payments WHERE status = 'Pending');
            END
        ");

        // Create Procedures
        DB::statement("
            CREATE PROCEDURE get_member_bookings(IN member_id_param INT)
            BEGIN
                SELECT * FROM member_bookings_view WHERE member_id = member_id_param ORDER BY schedule_time;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS get_member_bookings');
        DB::statement('DROP FUNCTION IF EXISTS get_pending_count');
        DB::statement('DROP FUNCTION IF EXISTS get_total_paid_amount');
        DB::statement('DROP VIEW IF EXISTS member_bookings_view');
        DB::statement('DROP VIEW IF EXISTS membership_distribution_view');
        DB::statement('DROP VIEW IF EXISTS pending_payments_view');
    }
};
