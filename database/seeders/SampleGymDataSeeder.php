<?php

namespace Database\Seeders;

use App\Models\Admin;
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
use Illuminate\Support\Facades\Hash;

class SampleGymDataSeeder extends Seeder
{
    /**
     * Seed the application's database with linked sample records.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::query()->updateOrCreate(
                ['email' => 'admin.demo@wedumbell.test'],
                [
                    'full_name' => 'Demo Admin',
                    'phone' => '09170000001',
                    'role' => 'Admin',
                    'status' => 'Active',
                    'password' => Hash::make('test1234'),
                    'last_visit_at' => now()->subHours(1),
                ]
            );

            Admin::query()->updateOrCreate(
                ['user_id' => $admin->id],
                []
            );

            $staffUsers = collect([
                [
                    'email' => 'lazarjhonn@gmail.com',
                    'full_name' => 'Lazar Jhonn',
                    'phone' => '09123456789',
                    'staff_role' => 'Receptionist',
                    'specialization' => 'Front Desk',
                ],
                [
                    'email' => 'coach.emma@wedumbell.test',
                    'full_name' => 'Emma Thompson',
                    'phone' => '09170000002',
                    'staff_role' => 'Trainer',
                    'specialization' => 'Yoga and Pilates',
                ],
                [
                    'email' => 'coach.james@wedumbell.test',
                    'full_name' => 'James Parker',
                    'phone' => '09170000003',
                    'staff_role' => 'Trainer',
                    'specialization' => 'Strength and Boxing',
                ],
            ])->map(function (array $staffData) {
                $user = User::query()->updateOrCreate(
                    ['email' => $staffData['email']],
                    [
                        'full_name' => $staffData['full_name'],
                        'phone' => $staffData['phone'],
                        'role' => 'Staff',
                        'status' => 'Active',
                        'password' => Hash::make('test1234'),
                        'last_visit_at' => now()->subMinutes(rand(15, 120)),
                    ]
                );

                $staff = Staff::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'role' => $staffData['staff_role'],
                        'specialization' => $staffData['specialization'],
                    ]
                );

                return ['user' => $user, 'staff' => $staff];
            });

            $memberUsers = collect([
                [
                    'email' => 'maria.santos@wedumbell.test',
                    'full_name' => 'Maria Santos',
                    'phone' => '09170000011',
                    'membership_type' => 'Premium',
                    'join_date' => now()->subMonths(4)->toDateString(),
                    'expiry_date' => now()->addMonths(8)->toDateString(),
                    'status' => 'Active',
                ],
                [
                    'email' => 'kevin.reyes@wedumbell.test',
                    'full_name' => 'Kevin Reyes',
                    'phone' => '09170000012',
                    'membership_type' => 'Basic',
                    'join_date' => now()->subMonths(2)->toDateString(),
                    'expiry_date' => now()->addMonths(10)->toDateString(),
                    'status' => 'Active',
                ],
                [
                    'email' => 'anna.dela-cruz@wedumbell.test',
                    'full_name' => 'Anna Dela Cruz',
                    'phone' => '09170000013',
                    'membership_type' => 'VIP',
                    'join_date' => now()->subMonth()->toDateString(),
                    'expiry_date' => now()->addMonths(11)->toDateString(),
                    'status' => 'Active',
                ],
            ])->map(function (array $memberData) {
                $user = User::query()->updateOrCreate(
                    ['email' => $memberData['email']],
                    [
                        'full_name' => $memberData['full_name'],
                        'phone' => $memberData['phone'],
                        'role' => 'Member',
                        'status' => 'Active',
                        'password' => Hash::make('test1234'),
                        'last_visit_at' => now()->subMinutes(rand(20, 300)),
                    ]
                );

                $member = Member::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => $memberData['phone'],
                        'membership_type' => $memberData['membership_type'],
                        'join_date' => $memberData['join_date'],
                        'expiry_date' => $memberData['expiry_date'],
                        'status' => $memberData['status'],
                    ]
                );

                return ['user' => $user, 'member' => $member];
            });

            $equipmentItems = collect([
                [
                    'name' => 'Treadmill Pro X1',
                    'quantity' => 6,
                    'status' => 'Available',
                    'condition_status' => 'Good',
                    'last_maintenance_date' => now()->subDays(15)->toDateString(),
                    'description' => 'Cardio treadmill for general gym use.',
                ],
                [
                    'name' => 'Spin Bike Elite',
                    'quantity' => 12,
                    'status' => 'In Use',
                    'condition_status' => 'Good',
                    'last_maintenance_date' => now()->subDays(25)->toDateString(),
                    'description' => 'Indoor cycling bikes used in spin classes.',
                ],
                [
                    'name' => 'Boxing Pad Set',
                    'quantity' => 8,
                    'status' => 'Maintenance',
                    'condition_status' => 'Under Repair',
                    'last_maintenance_date' => now()->subDays(40)->toDateString(),
                    'description' => 'Focus mitts and pads for boxing drills.',
                ],
            ])->map(fn (array $item) => Equipment::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            ));

            $classes = collect([
                [
                    'class_name' => 'Yoga Flow',
                    'schedule_time' => now()->startOfDay()->addHours(6)->addMinutes(30),
                    'max_slots' => 16,
                ],
                [
                    'class_name' => 'Spin Class',
                    'schedule_time' => now()->startOfDay()->addHours(9),
                    'max_slots' => 20,
                ],
                [
                    'class_name' => 'Boxing Fundamentals',
                    'schedule_time' => now()->startOfDay()->addHours(18),
                    'max_slots' => 12,
                ],
            ])->map(fn (array $classData) => GymClass::query()->updateOrCreate(
                ['class_name' => $classData['class_name'], 'schedule_time' => $classData['schedule_time']],
                ['max_slots' => $classData['max_slots']]
            ));

            $classes[0]->trainers()->syncWithoutDetaching([$staffUsers[1]['staff']->staff_id]);
            $classes[1]->trainers()->syncWithoutDetaching([$staffUsers[1]['staff']->staff_id]);
            $classes[2]->trainers()->syncWithoutDetaching([$staffUsers[2]['staff']->staff_id]);

            $classes[0]->equipments()->syncWithoutDetaching([$equipmentItems[0]->equipment_id]);
            $classes[1]->equipments()->syncWithoutDetaching([$equipmentItems[1]->equipment_id]);
            $classes[2]->equipments()->syncWithoutDetaching([$equipmentItems[2]->equipment_id]);

            $bookings = [
                [
                    'member_id' => $memberUsers[0]['member']->member_id,
                    'class_id' => $classes[0]->class_id,
                    'status' => 'Booked',
                ],
                [
                    'member_id' => $memberUsers[1]['member']->member_id,
                    'class_id' => $classes[1]->class_id,
                    'status' => 'Completed',
                ],
                [
                    'member_id' => $memberUsers[2]['member']->member_id,
                    'class_id' => $classes[2]->class_id,
                    'status' => 'Booked',
                ],
            ];

            foreach ($bookings as $bookingData) {
                Booking::query()->updateOrCreate(
                    [
                        'member_id' => $bookingData['member_id'],
                        'class_id' => $bookingData['class_id'],
                    ],
                    [
                        'status' => $bookingData['status'],
                        'created_at' => now()->subDays(2),
                    ]
                );
            }

            $attendanceRows = [
                [
                    'member_id' => $memberUsers[0]['member']->member_id,
                    'class_id' => $classes[0]->class_id,
                    'check_in_time' => now()->startOfDay()->addHours(6)->addMinutes(20),
                    'check_out_time' => now()->startOfDay()->addHours(7)->addMinutes(25),
                    'status' => 'Present',
                ],
                [
                    'member_id' => $memberUsers[1]['member']->member_id,
                    'class_id' => $classes[1]->class_id,
                    'check_in_time' => now()->startOfDay()->addHours(8)->addMinutes(50),
                    'check_out_time' => now()->startOfDay()->addHours(10)->addMinutes(5),
                    'status' => 'Present',
                ],
                [
                    'member_id' => $memberUsers[2]['member']->member_id,
                    'class_id' => $classes[2]->class_id,
                    'check_in_time' => now()->startOfDay()->addHours(17)->addMinutes(45),
                    'check_out_time' => null,
                    'status' => 'Present',
                ],
            ];

            foreach ($attendanceRows as $attendanceData) {
                Attendance::query()->updateOrCreate(
                    [
                        'member_id' => $attendanceData['member_id'],
                        'class_id' => $attendanceData['class_id'],
                        'check_in_time' => $attendanceData['check_in_time'],
                    ],
                    [
                        'check_out_time' => $attendanceData['check_out_time'],
                        'status' => $attendanceData['status'],
                    ]
                );
            }

            $payments = [
                [
                    'member_id' => $memberUsers[0]['member']->member_id,
                    'amount' => 2500.00,
                    'payment_date' => now()->subDays(5),
                    'payment_method' => 'GCash',
                    'status' => 'Paid',
                ],
                [
                    'member_id' => $memberUsers[1]['member']->member_id,
                    'amount' => 1500.00,
                    'payment_date' => now()->subDays(2),
                    'payment_method' => 'Cash',
                    'status' => 'Pending',
                ],
                [
                    'member_id' => $memberUsers[2]['member']->member_id,
                    'amount' => 3000.00,
                    'payment_date' => now()->subDay(),
                    'payment_method' => 'Card',
                    'status' => 'Failed',
                ],
            ];

            foreach ($payments as $paymentData) {
                Payment::query()->updateOrCreate(
                    [
                        'member_id' => $paymentData['member_id'],
                        'payment_date' => $paymentData['payment_date'],
                    ],
                    [
                        'amount' => $paymentData['amount'],
                        'payment_method' => $paymentData['payment_method'],
                        'status' => $paymentData['status'],
                    ]
                );
            }

            $this->command?->info('Sample gym data seeded successfully.');
            $this->command?->line('Admin login: admin.demo@wedumbell.test / test1234');
            $this->command?->line('Staff login: lazarjhonn@gmail.com / test1234');
        });
    }
}
