<?php

use App\Support\ManagedSqlFunctions;
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

        ManagedSqlFunctions::run('DROP FUNCTION IF EXISTS `get_membership_plan_price`', 'drop function get_membership_plan_price');

        ManagedSqlFunctions::run(<<<'SQL'
            CREATE FUNCTION get_membership_plan_price(plan_name_param VARCHAR(50)) RETURNS DECIMAL(12,2)
            READS SQL DATA
            BEGIN
                DECLARE plan_price DECIMAL(12,2);

                SELECT p.amount
                INTO plan_price
                FROM payments p
                WHERE p.status = 'Paid'
                  AND p.requested_membership_type COLLATE utf8mb4_general_ci = plan_name_param COLLATE utf8mb4_general_ci
                ORDER BY p.payment_date DESC
                LIMIT 1;

                IF plan_price IS NULL THEN
                    SELECT p.amount
                    INTO plan_price
                    FROM payments p
                    INNER JOIN members m ON m.member_id = p.member_id
                    WHERE p.status = 'Paid'
                      AND m.membership_type COLLATE utf8mb4_general_ci = plan_name_param COLLATE utf8mb4_general_ci
                    ORDER BY p.payment_date DESC
                    LIMIT 1;
                END IF;

                RETURN COALESCE(plan_price, 0.00);
            END
        SQL, 'create function get_membership_plan_price');
    }

    public function down(): void
    {
        return;

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        ManagedSqlFunctions::run('DROP FUNCTION IF EXISTS `get_membership_plan_price`', 'drop function get_membership_plan_price');

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
};
