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
use App\Support\DatabaseMetrics;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'users' => User::count(),
            'members' => DatabaseMetrics::totalMembers(),
            'staff' => Staff::count(),
            'classes' => GymClass::count(),
            'equipments' => Equipment::count(),
            'bookings' => Booking::count(),
            'attendances' => DatabaseMetrics::totalAttendances(),
            'active_members' => Member::where('status', 'Active')->count(),
            'today_attendance' => DatabaseMetrics::todayAttendanceCount(),
        ]);
    }
}
