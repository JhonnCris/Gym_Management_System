<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('reference_number', 60)->nullable()->after('payment_method');
            $table->string('gcash_number', 20)->nullable()->after('reference_number');
            $table->string('gcash_image_path')->nullable()->after('gcash_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['gcash_image_path', 'gcash_number', 'reference_number']);
        });
    }
};
