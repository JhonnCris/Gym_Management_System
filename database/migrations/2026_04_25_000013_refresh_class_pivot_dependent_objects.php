<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropProcedures();
        $this->dropViews();

        $this->createViewsForSingularPivotTables();
        $this->createProceduresForSingularPivotTables();
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropProcedures();
        $this->dropViews();

        $this->createViewsForPluralPivotTables();
        $this->createProceduresForPluralPivotTables();
    }

    private function dropViews(): void
    {
        foreach ([
            'member_bookings_view',
            'classes_with_bookings_view',
            'equipment_with_classes_view',
        ] as $view) {
            DB::unprepared("DROP VIEW IF EXISTS `{$view}`");
        }
    }

    private function dropProcedures(): void
    {
        foreach ([
            'cleanup_demo_data',
            'get_member_bookings',
            'get_member_booking_schedule',
        ] as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `{$procedure}`");
        }
    }

    private function createViewsForSingularPivotTables(): void
    {
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
    }

    private function createProceduresForSingularPivotTables(): void
    {
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

    private function createViewsForPluralPivotTables(): void
    {
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
            LEFT JOIN class_trainers ct ON ct.class_id = c.class_id
            LEFT JOIN staff s ON s.staff_id = ct.staff_id
            LEFT JOIN users tu ON tu.id = s.user_id
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
            LEFT JOIN class_trainers ct ON ct.class_id = c.class_id
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
            LEFT JOIN class_equipments ce ON ce.equipment_id = e.equipment_id
            GROUP BY
                e.equipment_id,
                e.name,
                e.quantity,
                e.status,
                e.condition_status,
                e.last_maintenance_date,
                e.description
        SQL);
    }

    private function createProceduresForPluralPivotTables(): void
    {
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
                FROM class_trainers ct
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
                FROM class_equipments ce
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
