<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('requested_membership_type', 50)->nullable()->after('payment_method');
            $table->timestamp('reviewed_at')->nullable()->after('requested_membership_type');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn(['requested_membership_type', 'reviewed_at']);
        });
    }
};
