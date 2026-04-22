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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->after('email');
            $table->enum('status', ['Active', 'Inactive', 'Suspended'])->default('Active')->after('role');
            $table->timestamp('last_visit_at')->nullable()->after('remember_token');
            $table->softDeletes();
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->date('expiry_date')->nullable()->after('join_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('expiry_date');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'status', 'last_visit_at']);
        });
    }
};
