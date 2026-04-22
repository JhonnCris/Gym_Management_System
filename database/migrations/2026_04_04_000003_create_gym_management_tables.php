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
        Schema::create('members', function (Blueprint $table) {
            $table->id('member_id');
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('membership_type', 50);
            $table->date('join_date');
            $table->enum('status', ['Active', 'Expired', 'Cancelled'])->default('Active');
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id('staff_id');
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['Trainer', 'Receptionist', 'Manager']);
            $table->string('specialization', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('equipments', function (Blueprint $table) {
            $table->id('equipment_id');
            $table->string('name', 100);
            $table->unsignedInteger('quantity')->default(0);
            $table->enum('status', ['Available', 'In Use', 'Maintenance'])->default('Available');
            $table->enum('condition_status', ['Good', 'Damaged', 'Under Repair'])->default('Good');
            $table->date('last_maintenance_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id('class_id');
            $table->string('class_name', 100);
            $table->dateTime('schedule_time');
            $table->unsignedInteger('max_slots');
            $table->timestamps();
        });

        Schema::create('class_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff', 'staff_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'staff_id']);
        });

        Schema::create('class_equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipments', 'equipment_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'equipment_id']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->foreignId('member_id')->constrained('members', 'member_id')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->enum('status', ['Booked', 'Cancelled', 'Completed'])->default('Booked');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['member_id', 'class_id']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->foreignId('member_id')->constrained('members', 'member_id')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes', 'class_id')->cascadeOnDelete();
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->enum('status', ['Present', 'Absent'])->default('Present');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('member_id')->constrained('members', 'member_id')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->dateTime('payment_date');
            $table->string('payment_method', 50);
            $table->enum('status', ['Paid', 'Pending', 'Failed'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('class_equipments');
        Schema::dropIfExists('class_trainers');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('equipments');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('members');
    }
};
