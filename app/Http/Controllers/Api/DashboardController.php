<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'users' => User::count(),
            'members' => (int) ($this->scalar('SELECT get_total_members() AS value') ?? 0),
            'staff' => Staff::count(),
            'classes' => GymClass::count(),
            'equipments' => Equipment::count(),
            'bookings' => Booking::count(),
            'attendances' => (int) ($this->scalar('SELECT get_total_attendances() AS value') ?? 0),
            'active_members' => Member::where('status', 'Active')->count(),
            'today_attendance' => (int) ($this->scalar('SELECT get_today_attendance_count() AS value') ?? 0),
        ]);
    }

    private function scalar(string $sql, array $bindings = []): mixed
    {
        return DB::selectOne($sql, $bindings)?->value;
    }
}
