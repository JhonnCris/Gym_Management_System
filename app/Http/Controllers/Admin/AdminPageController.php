<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use App\Support\DatabaseMetrics;
use Database\Seeders\SampleGymDataSeeder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminPageController extends Controller
{
    public function dashboard(): View
    {
        $this->ensureDashboardDataExists();

        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();

        $totalUsers = User::query()->count();
        $previousUsers = User::query()
            ->whereDate('created_at', '<', $monthStart->toDateString())
            ->count();
        $activeToday = DatabaseMetrics::todayUniqueMembersCount();
        $todayClassCount = DatabaseMetrics::classesTodayCount();
        $currentRevenue = DatabaseMetrics::monthRevenue($monthStart->year, $monthStart->month);
        $previousRevenue = DatabaseMetrics::monthRevenue($previousMonthStart->year, $previousMonthStart->month);
        $pendingPayments = Payment::getPendingCount();
        $equipmentIssues = DatabaseMetrics::equipmentIssuesCount();

        $membershipGrowth = collect(range(5, 0, -1))
            ->prepend(0)
            ->map(function (int $offset) use ($now): array {
                $date = $now->copy()->subMonths($offset);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                return [
                    'label' => $date->format('M'),
                    'count' => Member::query()
                        ->whereBetween('join_date', [$start->toDateString(), $end->toDateString()])
                        ->count(),
                ];
            });

        $membershipTypes = User::getMembershipDistribution()
            ->map(fn ($row): array => [
                'label' => $row->membership_type ?: 'Unassigned',
                'count' => (int) $row->aggregate,
            ]);

        $revenueOverview = collect(range(5, 0, -1))
            ->prepend(0)
            ->map(function (int $offset) use ($now): array {
                $date = $now->copy()->subMonths($offset);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                return [
                    'label' => $date->format('M'),
                    'amount' => DatabaseMetrics::monthRevenue($start->year, $start->month),
                ];
            });

        $attendanceCounts = Attendance::query()
            ->pluck('check_in_time')
            ->filter()
            ->map(fn ($time) => optional($time)->hour)
            ->countBy();

        $peakHourAnalysis = collect(range(0, 23))
            ->map(function (int $hour) use ($attendanceCounts, $now): array {
                return [
                    'label' => $now->copy()->setHour($hour)->format('ga'),
                    'visits' => (int) $attendanceCounts->get($hour, 0),
                ];
            });

        $quickStats = [
            [
                'label' => 'Pending approvals',
                'value' => $pendingPayments,
                'meta' => 'Membership payments waiting for review',
                'route' => route('admin.payments'),
                'button' => 'Review payments',
            ],
            [
                'label' => 'Classes today',
                'value' => $todayClassCount,
                'meta' => 'Scheduled sessions happening today',
                'route' => route('admin.classes'),
                'button' => 'Open classes',
            ],
            [
                'label' => 'Equipment issues',
                'value' => $equipmentIssues,
                'meta' => 'Items needing maintenance or repair follow-up',
                'route' => route('admin.equipment'),
                'button' => 'Check equipment',
            ],
        ];

        return view('admin.dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'user_change' => $this->percentChange($totalUsers, $previousUsers),
                'active_today' => $activeToday,
                'active_today_meta' => $todayClassCount > 0
                    ? $todayClassCount.' class schedule(s) running today'
                    : 'No classes scheduled today',
                'revenue' => $currentRevenue,
                'revenue_change' => $this->percentChange($currentRevenue, $previousRevenue),
            ],
            'membershipGrowth' => $this->buildLineChartData($membershipGrowth, 'count'),
            'membershipTypes' => $this->buildDonutChartData($membershipTypes),
            'revenueOverview' => $this->buildBarChartData($revenueOverview, 'amount'),
            'peakHourAnalysis' => $this->buildLineChartData($peakHourAnalysis, 'visits'),
            'quickStats' => $quickStats,
            'recentPayments' => Payment::summaryQuery()
                ->latest('payments.payment_date')
                ->take(5)
                ->get()
                ->map(function (object $payment): object {
                    $payment->payment_date = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;

                    return $payment;
                }),
        ]);
    }

    public function users(): View
    {
        return view('admin.user-management');
    }

    public function payments(): View
    {
        return view('admin.payments');
    }

    public function classes(): View
    {
        $this->ensureDashboardDataExists();

        $allClasses = GymClass::withBookings()
            ->orderBy('schedule_time')
            ->get()
            ->map(function (object $class) {
                $class->schedule_time = $class->schedule_time ? Carbon::parse($class->schedule_time) : null;
                $fillRate = $class->max_slots > 0
                    ? (int) round(($class->bookings_count / $class->max_slots) * 100)
                    : 0;

                $now = now();
                $scheduleState = $class->schedule_time->isFuture()
                    ? 'Upcoming'
                    : ($class->schedule_time->copy()->addHour()->isPast() ? 'Completed' : 'In Progress');

                $class->fill_rate = $fillRate;
                $class->trainer_names = $class->trainer_names ?: '';
                $class->schedule_state = $scheduleState;
                $class->schedule_state_class = match ($scheduleState) {
                    'Completed' => 'inactive',
                    'In Progress' => 'active',
                    default => 'role',
                };

                return $class;
            });

        $classes = $this->paginateCollection($allClasses, 15, 'admin_classes_page');

        return view('admin.classes', [
            'classes' => $classes,
            'stats' => [
                'total_classes' => $allClasses->count(),
                'upcoming_classes' => $allClasses->filter(fn ($class) => $class->schedule_time && $class->schedule_time->gte(now()))->count(),
                'todays_classes' => $allClasses->filter(fn ($class) => $class->schedule_time && $class->schedule_time->isToday())->count(),
                'bookings' => $allClasses->sum('bookings_count'),
                'average_fill_rate' => $allClasses->isEmpty() ? 0 : (int) round($allClasses->avg('fill_rate')),
                'near_capacity' => $allClasses->filter(fn ($class) => $class->fill_rate >= 80)->count(),
            ],
        ]);
    }

    public function attendance(): View
    {
        $this->ensureDashboardDataExists();

        $attendanceRecords = Attendance::query()
            ->withDetails()
            ->latest('attendances.check_in_time')
            ->paginate(15);

        $attendanceRecords->getCollection()->transform(function (object $attendance): object {
            $attendance->check_in_time = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
            $attendance->check_out_time = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

            return $attendance;
        });

        $weekStart = now()->startOfWeek();

        return view('admin.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'stats' => [
                'today_total' => DatabaseMetrics::todayAttendanceCount(),
                'today_unique_members' => DatabaseMetrics::todayUniqueMembersCount(),
                'currently_in' => DatabaseMetrics::currentlyInCount(),
                'week_total' => DatabaseMetrics::weekAttendanceCount(),
                'classes_touched' => Attendance::query()->where('check_in_time', '>=', $weekStart)->distinct('class_id')->count('class_id'),
            ],
        ]);
    }

    public function equipment(): View
    {
        $this->ensureDashboardDataExists();

        $allEquipment = Equipment::withClasses()
            ->orderBy('name')
            ->get()
            ->map(function (object $equipment): object {
                $equipment->last_maintenance_date = $equipment->last_maintenance_date
                    ? Carbon::parse($equipment->last_maintenance_date)
                    : null;

                return $equipment;
            });

        $equipmentItems = $this->paginateCollection($allEquipment, 15, 'admin_equipment_page');

        return view('admin.equipment', [
            'equipmentItems' => $equipmentItems,
            'stats' => [
                'tracked_items' => $allEquipment->count(),
                'total_units' => (int) $allEquipment->sum('quantity'),
                'available_items' => $allEquipment->where('status', 'Available')->count(),
                'attention_items' => DatabaseMetrics::equipmentIssuesCount(),
                'linked_to_classes' => $allEquipment->where('classes_count', '>', 0)->count(),
            ],
        ]);
    }

    public function reports(): View
    {
        $this->ensureDashboardDataExists();

        $now = now();

        $growthData = collect(range(6, 0, -1))
            ->prepend(0)
            ->map(function (int $offset) use ($now): array {
                $date = $now->copy()->subMonths($offset);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();

                return [
                    'label' => $date->format('M'),
                    'members' => Member::query()
                        ->whereBetween('join_date', [$start->toDateString(), $end->toDateString()])
                        ->count(),
                    'revenue' => DatabaseMetrics::monthRevenue($start->year, $start->month),
                ];
            });

        $attendanceHours = Attendance::query()
            ->get(['check_in_time'])
            ->map(fn (Attendance $attendance) => optional($attendance->check_in_time)?->hour)
            ->filter(fn ($hour) => $hour !== null);

        $peakHours = collect([
            ['label' => '6-8 AM', 'start' => 6, 'end' => 8],
            ['label' => '8-10 AM', 'start' => 8, 'end' => 10],
            ['label' => '10-12 NN', 'start' => 10, 'end' => 12],
            ['label' => '12-2 PM', 'start' => 12, 'end' => 14],
            ['label' => '4-6 PM', 'start' => 16, 'end' => 18],
            ['label' => '6-8 PM', 'start' => 18, 'end' => 20],
        ])->map(function (array $slot) use ($attendanceHours): array {
            return [
                'label' => $slot['label'],
                'visits' => $attendanceHours
                    ->filter(fn ($hour) => $hour >= $slot['start'] && $hour < $slot['end'])
                    ->count(),
            ];
        });

        $membershipDistribution = User::getMembershipDistribution()
            ->map(fn ($row): array => [
                'label' => $row->membership_type ?: 'Unassigned',
                'count' => (int) $row->aggregate,
            ]);

        $attendanceRows = Attendance::query()
            ->with(['member.user', 'gymClass'])
            ->orderByDesc('check_in_time')
            ->limit(12)
            ->get()
            ->map(function (Attendance $attendance): array {
                return [
                    'member_name' => $attendance->member?->user?->full_name ?? 'Unknown member',
                    'class_name' => $attendance->gymClass?->name ?? 'Walk-in / N/A',
                    'check_in_time' => optional($attendance->check_in_time)->format('M d, Y h:i A') ?? 'N/A',
                    'check_out_time' => optional($attendance->check_out_time)->format('M d, Y h:i A') ?? 'N/A',
                    'status' => $attendance->check_out_time ? 'Checked Out' : 'In Gym',
                ];
            })
            ->values()
            ->all();

        $reportStats = [
            'top_revenue_month' => $growthData->sortByDesc('revenue')->first(),
            'top_peak_slot' => $peakHours->sortByDesc('visits')->first(),
            'top_membership' => $membershipDistribution->sortByDesc('count')->first(),
            'total_revenue' => Payment::getTotalPaidAmount(),
            'total_visits' => DatabaseMetrics::totalAttendances(),
            'member_count' => DatabaseMetrics::totalMembers(),
        ];

        return view('admin.reports', [
            'growthReport' => $this->buildLineChartData($growthData, 'revenue'),
            'growthRows' => $growthData->values()->all(),
            'peakHoursReport' => $this->buildBarChartData($peakHours, 'visits'),
            'peakHoursRows' => $peakHours->values()->all(),
            'membershipDistribution' => $this->buildDonutChartData($membershipDistribution),
            'distributionRows' => $membershipDistribution->values()->all(),
            'attendanceRows' => $attendanceRows,
            'reportStats' => $reportStats,
        ]);
    }

    public function notifications(): JsonResponse
    {
        $now = now();

        $pendingPayments = Payment::pendingPayments()
            ->latest('payment_date')
            ->limit(8)
            ->get()
            ->map(function (object $payment): array {
                return [
                    'title' => 'Payment Review Required',
                    'message' => ($payment->full_name ?? ('Member #'.$payment->member_id))
                        .' submitted '
                        .($payment->membership_type ?: 'a membership payment')
                        .' via '.($payment->payment_method ?: 'Unknown method').'.',
                    'type' => 'warning',
                    'created_at' => $payment->payment_date ? Carbon::parse($payment->payment_date)->toIso8601String() : now()->toIso8601String(),
                ];
            });

        $failedPayments = Payment::recentFailedPayments(5)
            ->map(function (object $payment): array {
                return [
                    'title' => 'Failed Payment',
                    'message' => ($payment->member_name ?? ('Member #'.$payment->member_id))
                        .' has a failed payment record'
                        .($payment->requested_membership_type ? ' for '.$payment->requested_membership_type : '')
                        .'.',
                    'type' => 'info',
                    'created_at' => $payment->payment_date ? Carbon::parse($payment->payment_date)->toIso8601String() : now()->toIso8601String(),
                ];
            });

        $expiringMemberships = Member::query()
            ->with('user')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $now->toDateString())
            ->whereDate('expiry_date', '<=', $now->copy()->addDays(7)->toDateString())
            ->orderBy('expiry_date')
            ->limit(8)
            ->get()
            ->map(function (Member $member): array {
                return [
                    'title' => 'Membership Expiring Soon',
                    'message' => ($member->user?->full_name ?? ('Member #'.$member->member_id))
                        .' expires on '
                        .optional($member->expiry_date)?->format('M d, Y').'.',
                    'type' => 'info',
                    'created_at' => optional($member->expiry_date)?->startOfDay()->toIso8601String() ?? now()->toIso8601String(),
                ];
            });

        $newMembers = Member::query()
            ->with('user')
            ->whereDate('created_at', '>=', $now->copy()->subDays(7)->toDateString())
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (Member $member): array {
                return [
                    'title' => 'New Member Registered',
                    'message' => ($member->user?->full_name ?? ('Member #'.$member->member_id))
                        .' joined under the '.$member->membership_type.' plan.',
                    'type' => 'success',
                    'created_at' => optional($member->created_at)?->toIso8601String() ?? now()->toIso8601String(),
                ];
            });

        $fullClasses = GymClass::query()
            ->withCount('bookings')
            ->where('schedule_time', '>=', $now)
            ->where('schedule_time', '<=', $now->copy()->addDays(3))
            ->get()
            ->filter(fn (GymClass $class) => $class->bookings_count >= $class->max_slots)
            ->sortBy('schedule_time')
            ->take(5)
            ->map(function (GymClass $class): array {
                return [
                    'title' => 'Class Reached Capacity',
                    'message' => $class->class_name.' is fully booked for '.$class->schedule_time?->format('M d, h:i A').'.',
                    'type' => 'warning',
                    'created_at' => optional($class->schedule_time)?->toIso8601String() ?? now()->toIso8601String(),
                ];
            });

        $items = $pendingPayments
            ->concat($failedPayments)
            ->concat($expiringMemberships)
            ->concat($newMembers)
            ->concat($fullClasses)
            ->sortByDesc('created_at')
            ->take(20)
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    private function ensureDashboardDataExists(): void
    {
        $hasOperationalData = Member::query()->exists()
            || Payment::query()->exists()
            || Attendance::query()->exists()
            || GymClass::query()->exists()
            || Equipment::query()->exists();

        if (! $hasOperationalData) {
            app(SampleGymDataSeeder::class)->run();
        }
    }

    private function percentChange(float|int $current, float|int $previous): string
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;

        return sprintf('%s%d%%', $change >= 0 ? '+' : '', (int) round($change));
    }

    private function buildBarChartData(Collection $items, string $valueKey): array
    {
        $maxValue = max(1, (float) $items->max($valueKey));

        return [
            'max' => $maxValue,
            'items' => $items->map(function (array $item) use ($maxValue, $valueKey): array {
                $value = (float) $item[$valueKey];

                return [
                    'label' => $item['label'],
                    'value' => $value,
                    'height' => max(14, (int) round(($value / $maxValue) * 180)),
                ];
            })->all(),
        ];
    }

    private function buildDonutChartData(Collection $items): array
    {
        $palette = ['#111111', '#444444', '#737373', '#9a9a9a', '#c2c2c2'];
        $total = max(1, (int) $items->sum('count'));
        $circumference = 2 * pi() * 54;
        $offset = 0.0;

        $segments = $items->values()->map(function (array $item, int $index) use ($palette, $total, $circumference, &$offset): array {
            $portion = ((int) $item['count'] / $total);
            $length = $portion * $circumference;
            $segment = [
                'label' => $item['label'],
                'value' => (int) $item['count'],
                'percent' => (int) round($portion * 100),
                'color' => $palette[$index % count($palette)],
                'dasharray' => number_format($length, 2, '.', '').' '.number_format($circumference - $length, 2, '.', ''),
                'dashoffset' => number_format(-$offset, 2, '.', ''),
            ];

            $offset += $length;

            return $segment;
        })->all();

        return [
            'total' => $items->sum('count'),
            'segments' => $segments,
        ];
    }

    private function buildLineChartData(Collection $items, string $valueKey): array
    {
        $maxValue = max(1, (float) $items->max($valueKey));
        $count = max(1, $items->count());

        $points = $items->values()->map(function (array $item, int $index) use ($count, $maxValue, $valueKey): array {
            $x = $count > 1 ? ($index / ($count - 1)) * 100 : 50;
            $y = 100 - ((((float) $item[$valueKey]) / $maxValue) * 100);

            return [
                'label' => $item['label'],
                'value' => (float) $item[$valueKey],
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        })->all();

        return [
            'max' => $maxValue,
            'points' => $points,
            'polyline' => collect($points)->map(fn (array $point) => $point['x'].','.$point['y'])->implode(' '),
        ];
    }
}
