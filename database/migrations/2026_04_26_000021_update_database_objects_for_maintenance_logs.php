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

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop and recreate the equipment_with_classes_view without condition_status and last_maintenance_date
        DB::unprepared('DROP VIEW IF EXISTS `equipment_with_classes_view`');

        DB::unprepared(<<<'SQL'
            CREATE VIEW equipment_with_classes_view AS
            SELECT
                e.equipment_id,
                e.name,
                e.quantity,
                e.status,
                NULL AS condition_status,
                (SELECT MAX(created_at) FROM equipment_maintenance_logs eml WHERE eml.equipment_id = e.equipment_id) AS last_maintenance_date,
                e.description,
                COUNT(DISTINCT ce.class_id) AS classes_count
            FROM equipments e
            LEFT JOIN class_equipment ce ON ce.equipment_id = e.equipment_id
            GROUP BY
                e.equipment_id,
                e.name,
                e.quantity,
                e.status,
                e.description
        SQL);

        // Drop and recreate the get_equipment_issues_count function
        ManagedSqlFunctions::run('DROP FUNCTION IF EXISTS `get_equipment_issues_count`', 'drop function get_equipment_issues_count');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_equipment_issues_count() RETURNS INT
            READS SQL DATA
            BEGIN
                DECLARE equipment_count INT;

                SELECT COUNT(DISTINCT e.equipment_id)
                INTO equipment_count
                FROM equipments e
                LEFT JOIN equipment_maintenance_logs eml ON eml.equipment_id = e.equipment_id
                WHERE e.status = 'Maintenance'
                   OR (eml.status IN ('In Progress', 'Pending') AND eml.created_at >= (CURRENT_DATE - INTERVAL 30 DAY));

                RETURN equipment_count;
            END
        SQL, 'create function get_equipment_issues_count');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        return;

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Recreate the original view with condition_status and last_maintenance_date
        DB::unprepared('DROP VIEW IF EXISTS `equipment_with_classes_view`');

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

        // Recreate the original function
        ManagedSqlFunctions::run('DROP FUNCTION IF EXISTS `get_equipment_issues_count`', 'drop function get_equipment_issues_count');

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
    }
};
