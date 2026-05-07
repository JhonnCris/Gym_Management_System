<?php

use App\Support\ManagedSqlFunctions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        return;

        if (! $this->isMySql()) {
            return;
        }

        $this->dropProcedures();
        $this->dropFunctions();
        $this->dropViews();

        $this->createViews();
        $this->createFunctions();
        $this->createProcedures();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        return;

        if (! $this->isMySql()) {
            return;
        }

        $this->dropProcedures();
        $this->dropFunctions();
        $this->dropViews();
    }

    private function isMySql(): bool
    {
        return DB::getDriverName() === 'mysql';
    }

    private function dropViews(): void
    {
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
        ] as $view) {
            DB::unprepared("DROP VIEW IF EXISTS `{$view}`");
        }
    }

    private function dropFunctions(): void
    {
        foreach ([
            'get_membership_plan_price',
            'get_month_revenue',
            'get_monthly_revenue',
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

    private function dropProcedures(): void
    {
        foreach ([
            'cleanup_demo_data',
            'create_member_payment',
            'approve_payment',
            'get_members_joined_between',
            'get_attendances_by_date',
            'get_payments_by_status',
            'get_member_bookings',
            'get_member_attendances',
            'get_member_payments',
        ] as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `{$procedure}`");
        }
    }

    private function createViews(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE VIEW member_payment_summary AS
            SELECT
                p.payment_id,
                p.member_id,
                u.id AS user_id,
                u.full_name AS member_name,
                u.email AS member_email,
                m.membership_type,
                m.status AS member_status,
                m.join_date,
                m.expiry_date,
                p.amount,
                p.payment_method,
                p.reference_number,
                p.gcash_number,
                p.requested_membership_type,
                p.status AS payment_status,
                p.payment_date,
                p.reviewed_at,
                p.reviewed_by_user_id
            FROM payments p
            INNER JOIN members m ON m.member_id = p.member_id
            INNER JOIN users u ON u.id = m.user_id
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW pending_payments_view AS
            SELECT
                p.payment_id,
                p.member_id,
                u.full_name AS member_name,
                u.email AS member_email,
                m.membership_type,
                p.amount,
                p.payment_method,
                p.reference_number,
                p.gcash_number,
                p.requested_membership_type,
                p.payment_date,
                p.reviewed_at,
                p.reviewed_by_user_id
            FROM payments p
            INNER JOIN members m ON m.member_id = p.member_id
            INNER JOIN users u ON u.id = m.user_id
            WHERE p.status = 'Pending'
            ORDER BY p.payment_date DESC
            LIMIT 8
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW membership_distribution_view AS
            SELECT
                COALESCE(membership_type, 'Unassigned') AS membership_type,
                COUNT(*) AS aggregate
            FROM members
            GROUP BY membership_type
            ORDER BY aggregate DESC
        SQL);

        DB::unprepared(<<<'SQL'
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
                tu.full_name AS trainer_name
            FROM bookings b
            INNER JOIN classes c ON c.class_id = b.class_id
            LEFT JOIN class_trainer ct ON ct.class_id = c.class_id
            LEFT JOIN staff s ON s.staff_id = ct.staff_id
            LEFT JOIN users tu ON tu.id = s.user_id
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW attendance_recent_view AS
            SELECT
                a.attendance_id,
                a.member_id,
                u.full_name AS member_name,
                u.email AS member_email,
                a.class_id,
                c.class_name,
                a.check_in_time,
                a.check_out_time,
                a.status AS attendance_status
            FROM attendances a
            INNER JOIN members m ON m.member_id = a.member_id
            INNER JOIN users u ON u.id = m.user_id
            INNER JOIN classes c ON c.class_id = a.class_id
            ORDER BY a.check_in_time DESC
            LIMIT 20
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW classes_with_bookings_view AS
            SELECT
                c.class_id,
                c.class_name,
                c.schedule_time,
                c.max_slots,
                COUNT(DISTINCT b.booking_id) AS bookings_count,
                COUNT(DISTINCT ct.staff_id) AS trainer_count,
                GROUP_CONCAT(DISTINCT tu.full_name ORDER BY tu.full_name SEPARATOR ', ') AS trainer_names
            FROM classes c
            LEFT JOIN bookings b ON b.class_id = c.class_id
            LEFT JOIN class_trainer ct ON ct.class_id = c.class_id
            LEFT JOIN staff s ON s.staff_id = ct.staff_id
            LEFT JOIN users tu ON tu.id = s.user_id
            GROUP BY c.class_id, c.class_name, c.schedule_time, c.max_slots
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW equipment_with_classes_view AS
            SELECT
                e.equipment_id,
                e.name,
                e.quantity,
                e.status,
                e.condition_status,
                e.last_maintenance_date,
                e.description,
                COUNT(DISTINCT ce.class_id) AS classes_count
            FROM equipments e
            LEFT JOIN class_equipment ce ON ce.equipment_id = e.equipment_id
            GROUP BY
                e.equipment_id,
                e.name,
                e.quantity,
                e.status,
                e.condition_status,
                e.last_maintenance_date,
                e.description
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW payment_methods_view AS
            SELECT DISTINCT payment_method
            FROM payments
            WHERE payment_method IS NOT NULL
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW failed_payments_recent_view AS
            SELECT
                p.payment_id,
                p.member_id,
                u.full_name AS member_name,
                u.email AS member_email,
                m.membership_type,
                p.amount,
                p.payment_method,
                p.reference_number,
                p.requested_membership_type,
                p.payment_date,
                p.reviewed_at,
                p.reviewed_by_user_id
            FROM payments p
            INNER JOIN members m ON m.member_id = p.member_id
            INNER JOIN users u ON u.id = m.user_id
            WHERE p.status = 'Failed'
              AND p.payment_date >= (CURRENT_DATE - INTERVAL 14 DAY)
            ORDER BY p.payment_date DESC
        SQL);
    }

    private function createFunctions(): void
    {
        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_total_paid_amount() RETURNS DECIMAL(12,2)
            READS SQL DATA
            BEGIN
                DECLARE total_amount DECIMAL(12,2);

                SELECT COALESCE(SUM(amount), 0.00)
                INTO total_amount
                FROM payments
                WHERE status = 'Paid';

                RETURN total_amount;
            END
        SQL, 'create function get_total_paid_amount');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_pending_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE pending_count INT;

                SELECT COUNT(*)
                INTO pending_count
                FROM payments
                WHERE status = 'Pending';

                RETURN pending_count;
            END
        SQL, 'create function get_pending_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_total_members() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE member_count INT;

                SELECT COUNT(*)
                INTO member_count
                FROM members;

                RETURN member_count;
            END
        SQL, 'create function get_total_members');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_total_attendances() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE attendance_count INT;

                SELECT COUNT(*)
                INTO attendance_count
                FROM attendances;

                RETURN attendance_count;
            END
        SQL, 'create function get_total_attendances');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_today_attendance_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE attendance_count INT;

                SELECT COUNT(*)
                INTO attendance_count
                FROM attendances
                WHERE DATE(check_in_time) = CURRENT_DATE;

                RETURN attendance_count;
            END
        SQL, 'create function get_today_attendance_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_today_unique_members_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE member_count INT;

                SELECT COUNT(DISTINCT member_id)
                INTO member_count
                FROM attendances
                WHERE DATE(check_in_time) = CURRENT_DATE;

                RETURN member_count;
            END
        SQL, 'create function get_today_unique_members_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_currently_in_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE member_count INT;

                SELECT COUNT(*)
                INTO member_count
                FROM attendances
                WHERE DATE(check_in_time) = CURRENT_DATE
                  AND check_out_time IS NULL;

                RETURN member_count;
            END
        SQL, 'create function get_currently_in_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_week_attendance_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE attendance_count INT;

                SELECT COUNT(*)
                INTO attendance_count
                FROM attendances
                WHERE YEARWEEK(check_in_time, 1) = YEARWEEK(CURRENT_DATE, 1);

                RETURN attendance_count;
            END
        SQL, 'create function get_week_attendance_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_classes_today_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE class_count INT;

                SELECT COUNT(*)
                INTO class_count
                FROM classes
                WHERE DATE(schedule_time) = CURRENT_DATE;

                RETURN class_count;
            END
        SQL, 'create function get_classes_today_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_equipment_issues_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE equipment_count INT;

                SELECT COUNT(*)
                INTO equipment_count
                FROM equipments
                WHERE status = 'Maintenance'
                   OR condition_status <> 'Good';

                RETURN equipment_count;
            END
        SQL, 'create function get_equipment_issues_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_month_revenue(year_param INT, month_param INT) RETURNS DECIMAL(12,2)
            READS SQL DATA
            BEGIN
                DECLARE total_revenue DECIMAL(12,2);

                SELECT COALESCE(SUM(amount), 0.00)
                INTO total_revenue
                FROM payments
                WHERE status = 'Paid'
                  AND YEAR(payment_date) = year_param
                  AND MONTH(payment_date) = month_param;

                RETURN total_revenue;
            END
        SQL, 'create function get_month_revenue');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_membership_plan_price(plan_name_param VARCHAR(50)) RETURNS DECIMAL(12,2)
            READS SQL DATA
            BEGIN
                DECLARE plan_price DECIMAL(12,2);

                SELECT p.amount
                INTO plan_price
                FROM payments p
                WHERE p.status = 'Paid'
                  AND p.requested_membership_type = plan_name_param
                ORDER BY p.payment_date DESC
                LIMIT 1;

                IF plan_price IS NULL THEN
                    SELECT p.amount
                    INTO plan_price
                    FROM payments p
                    INNER JOIN members m ON m.member_id = p.member_id
                    WHERE p.status = 'Paid'
                      AND m.membership_type = plan_name_param
                    ORDER BY p.payment_date DESC
                    LIMIT 1;
                END IF;

                RETURN COALESCE(plan_price, 0.00);
            END
        SQL, 'create function get_membership_plan_price');
    }

    private function createProcedures(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_member_payments(IN member_id_param BIGINT)
            BEGIN
                SELECT *
                FROM member_payment_summary
                WHERE member_id = member_id_param
                ORDER BY payment_date DESC;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_member_attendances(IN member_id_param BIGINT)
            BEGIN
                SELECT
                    a.attendance_id,
                    a.member_id,
                    u.full_name AS member_name,
                    u.email AS member_email,
                    a.class_id,
                    c.class_name,
                    a.check_in_time,
                    a.check_out_time,
                    a.status AS attendance_status
                FROM attendances a
                INNER JOIN members m ON m.member_id = a.member_id
                INNER JOIN users u ON u.id = m.user_id
                INNER JOIN classes c ON c.class_id = a.class_id
                WHERE a.member_id = member_id_param
                ORDER BY a.check_in_time DESC;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_member_bookings(IN member_id_param BIGINT)
            BEGIN
                SELECT *
                FROM member_bookings_view
                WHERE member_id = member_id_param
                ORDER BY schedule_time;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_payments_by_status(IN status_param VARCHAR(20))
            BEGIN
                SELECT *
                FROM member_payment_summary
                WHERE payment_status = status_param
                ORDER BY payment_date DESC;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_attendances_by_date(IN attendance_date_param DATE)
            BEGIN
                SELECT
                    a.attendance_id,
                    a.member_id,
                    u.full_name AS member_name,
                    u.email AS member_email,
                    a.class_id,
                    c.class_name,
                    a.check_in_time,
                    a.check_out_time,
                    a.status AS attendance_status
                FROM attendances a
                INNER JOIN members m ON m.member_id = a.member_id
                INNER JOIN users u ON u.id = m.user_id
                INNER JOIN classes c ON c.class_id = a.class_id
                WHERE DATE(a.check_in_time) = attendance_date_param
                ORDER BY a.check_in_time DESC;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE get_members_joined_between(IN start_date_param DATE, IN end_date_param DATE)
            BEGIN
                SELECT
                    m.member_id,
                    m.user_id,
                    u.full_name,
                    u.email,
                    m.membership_type,
                    m.join_date,
                    m.expiry_date,
                    m.status
                FROM members m
                INNER JOIN users u ON u.id = m.user_id
                WHERE m.join_date BETWEEN start_date_param AND end_date_param
                ORDER BY m.join_date DESC, m.member_id DESC;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE approve_payment(IN payment_id_param BIGINT, IN reviewer_user_id_param BIGINT)
            BEGIN
                DECLARE member_id_value BIGINT;
                DECLARE requested_membership_type_value VARCHAR(50);

                START TRANSACTION;

                SELECT p.member_id, p.requested_membership_type
                INTO member_id_value, requested_membership_type_value
                FROM payments p
                WHERE p.payment_id = payment_id_param
                FOR UPDATE;

                UPDATE payments
                SET status = 'Paid',
                    reviewed_at = NOW(),
                    reviewed_by_user_id = reviewer_user_id_param
                WHERE payment_id = payment_id_param
                  AND status = 'Pending';

                IF ROW_COUNT() > 0 THEN
                    UPDATE members
                    SET membership_type = COALESCE(requested_membership_type_value, membership_type),
                        status = 'Active',
                        expiry_date = DATE_ADD(
                            CASE
                                WHEN expiry_date IS NOT NULL AND expiry_date > CURRENT_DATE THEN expiry_date
                                ELSE CURRENT_DATE
                            END,
                            INTERVAL 1 MONTH
                        )
                    WHERE member_id = member_id_value;
                END IF;

                COMMIT;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE create_member_payment(
                IN member_id_param BIGINT,
                IN amount_param DECIMAL(10,2),
                IN method_param VARCHAR(50),
                IN reference_param VARCHAR(60)
            )
            BEGIN
                INSERT INTO payments (
                    member_id,
                    amount,
                    payment_date,
                    payment_method,
                    reference_number,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    member_id_param,
                    amount_param,
                    NOW(),
                    method_param,
                    reference_param,
                    'Pending',
                    NOW(),
                    NOW()
                );
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE cleanup_demo_data()
            BEGIN
                START TRANSACTION;

                DELETE a
                FROM attendances a
                INNER JOIN members m ON m.member_id = a.member_id
                INNER JOIN users u ON u.id = m.user_id
                WHERE u.email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                DELETE b
                FROM bookings b
                INNER JOIN members m ON m.member_id = b.member_id
                INNER JOIN users u ON u.id = m.user_id
                WHERE u.email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                DELETE p
                FROM payments p
                INNER JOIN members m ON m.member_id = p.member_id
                INNER JOIN users u ON u.id = m.user_id
                WHERE u.email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                DELETE ct
                FROM class_trainer ct
                INNER JOIN staff s ON s.staff_id = ct.staff_id
                INNER JOIN users u ON u.id = s.user_id
                WHERE u.email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                DELETE ce
                FROM class_equipment ce
                INNER JOIN equipments e ON e.equipment_id = ce.equipment_id
                WHERE e.name IN (
                    'Treadmill Pro X1',
                    'Spin Bike Elite',
                    'Boxing Pad Set'
                );

                DELETE FROM classes
                WHERE class_name IN (
                    'Yoga Flow',
                    'Spin Class',
                    'Boxing Fundamentals'
                );

                DELETE FROM equipments
                WHERE name IN (
                    'Treadmill Pro X1',
                    'Spin Bike Elite',
                    'Boxing Pad Set'
                );

                DELETE m
                FROM members m
                INNER JOIN users u ON u.id = m.user_id
                WHERE u.email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                DELETE s
                FROM staff s
                INNER JOIN users u ON u.id = s.user_id
                WHERE u.email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                DELETE FROM users
                WHERE email IN (
                    'admin.demo@wedumbell.test',
                    'lazarjhonn@gmail.com',
                    'coach.emma@wedumbell.test',
                    'coach.james@wedumbell.test',
                    'maria.santos@wedumbell.test',
                    'kevin.reyes@wedumbell.test',
                    'anna.dela-cruz@wedumbell.test'
                );

                COMMIT;
            END
        SQL);
    }
};
