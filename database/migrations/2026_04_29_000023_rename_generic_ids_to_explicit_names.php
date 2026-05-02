<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->renameMembershipPlanPrimaryKey('id', 'mem_plan_id');
        $this->renameColumnIfPresent('class_trainer', 'id', 'trainer_id');
        $this->renameColumnIfPresent('class_equipment', 'id', 'cl_equpment_id');
        $this->renameColumnIfPresent('equipment_maintenance_logs', 'id', 'eml_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->renameMembershipPlanPrimaryKey('mem_plan_id', 'id');
        $this->renameColumnIfPresent('class_trainer', 'trainer_id', 'id');
        $this->renameColumnIfPresent('class_equipment', 'cl_equpment_id', 'id');
        $this->renameColumnIfPresent('equipment_maintenance_logs', 'eml_id', 'id');
    }

    private function renameMembershipPlanPrimaryKey(string $from, string $to): void
    {
        if (! Schema::hasTable('membership_plans')
            || ! Schema::hasColumn('membership_plans', $from)
            || Schema::hasColumn('membership_plans', $to)) {
            return;
        }

        $this->dropForeignKeyIfExists('members', 'membership_plan_id');
        $this->dropForeignKeyIfExists('payments', 'requested_membership_plan_id');
        $this->renameColumnIfPresent('membership_plans', $from, $to);

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'membership_plan_id')) {
            Schema::table('members', function (Blueprint $table) use ($to): void {
                $table->foreign('membership_plan_id')
                    ->references($to)
                    ->on('membership_plans')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'requested_membership_plan_id')) {
            Schema::table('payments', function (Blueprint $table) use ($to): void {
                $table->foreign('requested_membership_plan_id')
                    ->references($to)
                    ->on('membership_plans')
                    ->nullOnDelete();
            });
        }
    }

    private function renameColumnIfPresent(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($from, $to): void {
            $blueprint->renameColumn($from, $to);
        });
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $database = DB::getDatabaseName();
            $foreignKeys = DB::select(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$database, $table, $column]
            );

            foreach ($foreignKeys as $foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                    $table,
                    $foreignKey->CONSTRAINT_NAME
                ));
            }

            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->dropForeign([$column]);
            });
        } catch (\Throwable) {
            // Ignore when the foreign key is already absent.
        }
    }
};
