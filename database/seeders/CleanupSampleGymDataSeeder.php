<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanupSampleGymDataSeeder extends Seeder
{
    /**
     * Remove the linked demo data created by SampleGymDataSeeder.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $demoEmails = [
                'admin.demo@wedumbell.test',
                'lazarjhonn@gmail.com',
                'coach.emma@wedumbell.test',
                'coach.james@wedumbell.test',
                'maria.santos@wedumbell.test',
                'kevin.reyes@wedumbell.test',
                'anna.dela-cruz@wedumbell.test',
            ];

            $demoClassNames = [
                'Yoga Flow',
                'Spin Class',
                'Boxing Fundamentals',
            ];

            $demoEquipmentNames = [
                'Treadmill Pro X1',
                'Spin Bike Elite',
                'Boxing Pad Set',
            ];

            $userIds = User::query()
                ->whereIn('email', $demoEmails)
                ->pluck('id');

            $memberIds = Member::query()
                ->whereIn('user_id', $userIds)
                ->pluck('member_id');

            $staffIds = Staff::query()
                ->whereIn('user_id', $userIds)
                ->pluck('staff_id');

            $classIds = GymClass::query()
                ->whereIn('class_name', $demoClassNames)
                ->pluck('class_id');

            $equipmentIds = Equipment::query()
                ->whereIn('name', $demoEquipmentNames)
                ->pluck('equipment_id');

            Attendance::query()->whereIn('member_id', $memberIds)->delete();
            Booking::query()->whereIn('member_id', $memberIds)->delete();
            Payment::query()->whereIn('member_id', $memberIds)->delete();

            DB::table('class_trainer')->whereIn('staff_id', $staffIds)->delete();
            DB::table('class_equipment')->whereIn('equipment_id', $equipmentIds)->delete();

            GymClass::query()->whereIn('class_id', $classIds)->delete();
            Equipment::query()->whereIn('equipment_id', $equipmentIds)->delete();
            Member::query()->whereIn('member_id', $memberIds)->delete();
            Staff::query()->whereIn('staff_id', $staffIds)->delete();
            User::query()->whereIn('id', $userIds)->delete();

            $this->command?->info('Sample gym data cleaned up successfully.');
        });
    }
}
