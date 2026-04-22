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
        Schema::create('admins', function (Blueprint $table) {
            $table->id('admin_id');
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        $timestamp = now();
        $adminUserIds = DB::table('users')
            ->where('role', 'Admin')
            ->pluck('id');

        if ($adminUserIds->isNotEmpty()) {
            DB::table('admins')->insert(
                $adminUserIds->map(fn (int $userId): array => [
                    'user_id' => $userId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all()
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
