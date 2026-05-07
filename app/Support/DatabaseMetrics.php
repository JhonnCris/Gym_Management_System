<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;

class DatabaseMetrics
{
    public static function totalMembers(): int
    {
        return Member::query()->count();
    }

    public static function totalAttendances(): int
    {
        return Attendance::query()->count();
    }

    public static function todayAttendanceCount(): int
    {
        return Attendance::query()
            ->whereDate('check_in_time', today())
            ->count();
    }

    public static function todayUniqueMembersCount(): int
    {
        return Attendance::query()
            ->whereDate('check_in_time', today())
            ->distinct()
            ->count('member_id');
    }

    public static function currentlyInCount(): int
    {
        return Attendance::query()
            ->whereDate('check_in_time', today())
            ->whereNull('check_out_time')
            ->count();
    }

    public static function weekAttendanceCount(): int
    {
        return Attendance::query()
            ->whereBetween('check_in_time', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    public static function classesTodayCount(): int
    {
        return GymClass::query()
            ->whereDate('schedule_time', today())
            ->count();
    }

    public static function equipmentIssuesCount(): int
    {
        return Equipment::query()
            ->leftJoin('equipment_maintenance_logs as eml', 'eml.equipment_id', '=', 'equipments.equipment_id')
            ->where(function ($query): void {
                $query->where('equipments.status', 'Maintenance')
                    ->orWhere(function ($maintenanceQuery): void {
                        $maintenanceQuery
                            ->whereIn('eml.status', ['In Progress', 'Pending'])
                            ->where('eml.created_at', '>=', now()->subDays(30));
                    });
            })
            ->distinct()
            ->count('equipments.equipment_id');
    }

    public static function monthRevenue(int $year, int $month): float
    {
        return (float) Payment::query()
            ->where('status', 'Paid')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');
    }
}
