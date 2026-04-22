<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
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
        $activeToday = (int) ($this->scalar('SELECT get_today_unique_members_count() AS value') ?? 0);
        $todayClassCount = (int) ($this->scalar('SELECT get_classes_today_count() AS value') ?? 0);
        $currentRevenue = (float) ($this->scalar(
            'SELECT get_month_revenue(?, ?) AS value',
            [$monthStart->year, $monthStart->month]
        ) ?? 0);
        $previousRevenue = (float) ($this->scalar(
            'SELECT get_month_revenue(?, ?) AS value',
            [$previousMonthStart->year, $previousMonthStart->month]
        ) ?? 0);
        $pendingPayments = (int) ($this->scalar('SELECT get_pending_count() AS value') ?? 0);
        $equipmentIssues = (int) ($this->scalar('SELECT get_equipment_issues_count() AS value') ?? 0);

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

        $membershipTypes = DB::table('membership_distribution_view')
            ->orderByDesc('aggregate')
            ->get()
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
                    'amount' => (float) ($this->scalar(
                        'SELECT get_month_revenue(?, ?) AS value',
                        [$start->year, $start->month]
                    ) ?? 0),
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
            'recentPayments' => DB::table('member_payment_summary')
                ->latest('payment_date')
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

        $classes = DB::table('classes_with_bookings_view')
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

        return view('admin.classes', [
            'classes' => $classes,
            'stats' => [
                'total_classes' => $classes->count(),
                'upcoming_classes' => $classes->where('schedule_time', '>=', now())->count(),
                'todays_classes' => $classes->filter(fn ($class) => $class->schedule_time->isToday())->count(),
                'bookings' => $classes->sum('bookings_count'),
                'average_fill_rate' => $classes->isEmpty() ? 0 : (int) round($classes->avg('fill_rate')),
                'near_capacity' => $classes->where('fill_rate', '>=', 80)->count(),
            ],
        ]);
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:120',
            'schedule_time' => 'required|date_format:Y-m-d\TH:i',
            'max_slots' => 'required|integer|min:1',
        ]);

        GymClass::create($validated);

        return redirect()->route('admin.classes')->with('success', 'New class added successfully.');
    }

    public function attendance(): View
    {
        $this->ensureDashboardDataExists();

        $attendanceRecords = DB::table('attendance_recent_view')
            ->latest('check_in_time')
            ->get()
            ->map(function (object $attendance): object {
                $attendance->check_in_time = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
                $attendance->check_out_time = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

                return $attendance;
            });

        $weekStart = now()->startOfWeek();

        return view('admin.attendance', [
            'attendanceRecords' => $attendanceRecords,
            'stats' => [
                'today_total' => (int) ($this->scalar('SELECT get_today_attendance_count() AS value') ?? 0),
                'today_unique_members' => (int) ($this->scalar('SELECT get_today_unique_members_count() AS value') ?? 0),
                'currently_in' => (int) ($this->scalar('SELECT get_currently_in_count() AS value') ?? 0),
                'week_total' => (int) ($this->scalar('SELECT get_week_attendance_count() AS value') ?? 0),
                'classes_touched' => Attendance::query()->where('check_in_time', '>=', $weekStart)->distinct('class_id')->count('class_id'),
            ],
        ]);
    }

    public function equipment(): View
    {
        $this->ensureDashboardDataExists();

        $equipmentItems = DB::table('equipment_with_classes_view')
            ->orderBy('name')
            ->get()
            ->map(function (object $equipment): object {
                $equipment->last_maintenance_date = $equipment->last_maintenance_date
                    ? Carbon::parse($equipment->last_maintenance_date)
                    : null;

                return $equipment;
            });

        return view('admin.equipment', [
            'equipmentItems' => $equipmentItems,
            'stats' => [
                'tracked_items' => $equipmentItems->count(),
                'total_units' => (int) $equipmentItems->sum('quantity'),
                'available_items' => $equipmentItems->where('status', 'Available')->count(),
                'attention_items' => (int) ($this->scalar('SELECT get_equipment_issues_count() AS value') ?? 0),
                'linked_to_classes' => $equipmentItems->where('classes_count', '>', 0)->count(),
            ],
        ]);
    }

    public function storeEquipment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|string|in:Available,Maintenance',
            'condition_status' => 'required|string|in:Good,Fair,Needs Repair',
            'last_maintenance_date' => 'nullable|date',
            'description' => 'nullable|string|max:500',
        ]);

        Equipment::create($validated);

        return redirect()->route('admin.equipment')->with('success', 'Equipment item added successfully.');
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
                    'revenue' => (float) ($this->scalar(
                        'SELECT get_month_revenue(?, ?) AS value',
                        [$start->year, $start->month]
                    ) ?? 0),
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

        $membershipDistribution = DB::table('membership_distribution_view')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->membership_type ?: 'Unassigned',
                'count' => (int) $row->aggregate,
            ]);

        $reportStats = [
            'top_revenue_month' => $growthData->sortByDesc('revenue')->first(),
            'top_peak_slot' => $peakHours->sortByDesc('visits')->first(),
            'top_membership' => $membershipDistribution->sortByDesc('count')->first(),
            'total_revenue' => (float) ($this->scalar('SELECT get_total_paid_amount() AS value') ?? 0),
            'total_visits' => (int) ($this->scalar('SELECT get_total_attendances() AS value') ?? 0),
            'member_count' => (int) ($this->scalar('SELECT get_total_members() AS value') ?? 0),
        ];

        return view('admin.reports', [
            'growthReport' => $this->buildLineChartData($growthData, 'revenue'),
            'growthRows' => $growthData->values()->all(),
            'peakHoursReport' => $this->buildBarChartData($peakHours, 'visits'),
            'peakHoursRows' => $peakHours->values()->all(),
            'membershipDistribution' => $this->buildDonutChartData($membershipDistribution),
            'distributionRows' => $membershipDistribution->values()->all(),
            'reportStats' => $reportStats,
        ]);
    }

    public function notifications(): JsonResponse
    {
        $now = now();

        $pendingPayments = DB::table('pending_payments_view')
            ->latest('payment_date')
            ->limit(8)
            ->get()
            ->map(function (object $payment): array {
                return [
                    'title' => 'Payment Review Required',
                    'message' => ($payment->member_name ?? ('Member #'.$payment->member_id))
                        .' submitted '
                        .($payment->requested_membership_type ?: 'a membership payment')
                        .' via '.($payment->payment_method ?: 'Unknown method').'.',
                    'type' => 'warning',
                    'created_at' => $payment->payment_date ? Carbon::parse($payment->payment_date)->toIso8601String() : now()->toIso8601String(),
                ];
            });

        $failedPayments = DB::table('failed_payments_recent_view')
            ->latest('payment_date')
            ->limit(5)
            ->get()
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

    private function scalar(string $sql, array $bindings = []): mixed
    {
        return DB::selectOne($sql, $bindings)?->value;
    }
}
