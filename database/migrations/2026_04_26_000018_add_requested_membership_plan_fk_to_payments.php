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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('requested_membership_plan_id')->nullable()->after('member_id')->constrained('membership_plans', 'mem_plan_id')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['requested_membership_plan_id']);
            $table->dropColumn('requested_membership_plan_id');
        });
    }
};
