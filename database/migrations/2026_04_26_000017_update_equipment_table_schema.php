<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            // Drop condition_status and last_maintenance_date columns
            // These are replaced by equipment_maintenance_logs table
            if (Schema::hasColumn('equipments', 'condition_status')) {
                $table->dropColumn('condition_status');
            }
            if (Schema::hasColumn('equipments', 'last_maintenance_date')) {
                $table->dropColumn('last_maintenance_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->enum('condition_status', ['Good', 'Damaged', 'Under Repair'])->default('Good')->after('status');
            $table->date('last_maintenance_date')->nullable()->after('condition_status');
        });
    }
};
