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
        Schema::create('equipment_maintenance_logs', function (Blueprint $table) {
            $table->id('eml_id');
            $table->foreignId('equipment_id')->constrained('equipments', 'equipment_id')->cascadeOnDelete();
            $table->enum('maintenance_type', ['Inspection', 'Repair', 'Cleaning', 'Parts Replacement'])->default('Inspection');
            $table->enum('status', ['Scheduled', 'In Progress', 'Completed', 'Pending'])->default('Pending');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->dateTime('performed_date')->nullable();
            $table->dateTime('next_scheduled_date')->nullable();
            $table->timestamps();

            // Foreign key for performed_by (staff_id) - using unsignedBigInteger reference
            $table->foreign('performed_by')->references('staff_id')->on('staff')->nullOnDelete();

            // Composite index for quick lookups by equipment and date
            $table->index(['equipment_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenance_logs');
    }
};
