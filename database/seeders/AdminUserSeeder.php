<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'johnnlazar321@gmail.com'],
            [
                'full_name' => 'System Admin',
                'phone' => '09171234567',
                'role' => 'Admin',
                'status' => 'Active',
                'password' => Hash::make('test123'),
            ]
        );

        Admin::query()->updateOrCreate(
            ['user_id' => $user->id],
            []
        );
    }
}
