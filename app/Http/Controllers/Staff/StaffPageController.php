<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffPageController extends Controller
{
    public function dashboard(): View
    {
        $classes = $this->todaysClassesCollection();

        return view('staff.dashboard', [
            'stats' => $this->checkInStats(),
            'todaysClassesCount' => (int) ($this->scalar('SELECT get_classes_today_count() AS value') ?? 0),
            'todaysSchedule' => $classes->take(5),
            'recentCheckIns' => $this->attendanceForDate(today()->toDateString())->take(5),
        ]);
    }

    public function checkin(Request $request): View
    {
        $lookup = trim((string) $request->query('lookup'));
        $memberPreview = $lookup !== '' ? $this->findMember($lookup) : null;

        return view('staff.check-in', [
            'stats' => $this->checkInStats(),
            'todaysClassOptions' => DB::table('classes')
                ->whereDate('schedule_time', today())
                ->orderBy('schedule_time')
                ->get()
                ->map(function (object $class): object {
                    $class->schedule_time = $class->schedule_time ? Carbon::parse($class->schedule_time) : null;

                    return $class;
                }),
            'memberPreview' => $memberPreview,
            'memberPreviewAttendance' => $memberPreview
                ? Attendance::query()
                    ->where('member_id', $memberPreview->member_id)
                    ->whereNull('check_out_time')
                    ->latest('check_in_time')
                    ->first()
                : null,
            'recentCheckIns' => $this->attendanceForDate(today()->toDateString())->take(6),
        ]);
    }

    public function storeCheckin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_lookup' => ['required', 'string', 'max:120'],
            'class_id' => ['nullable', 'integer', 'exists:classes,class_id'],
            'attendance_action' => ['required', 'in:check_in,check_out'],
        ]);

        $member = $this->findMember($validated['member_lookup']);

        if (! $member) {
            return back()->withInput()->withErrors([
                'member_lookup' => 'Member not found. Enter a valid member ID, email, or full name.',
            ]);
        }

        if ($member->status !== 'Active' || $member->user?->status !== 'Active') {
            return back()->withInput()->withErrors([
                'member_lookup' => 'Only active members can use gym attendance actions.',
            ]);
        }

        $activeAttendance = Attendance::query()
            ->where('member_id', $member->member_id)
            ->whereNull('check_out_time')
            ->latest('check_in_time')
            ->first();

        if ($validated['attendance_action'] === 'check_out') {
            if (! $activeAttendance) {
                return back()->withInput()->withErrors([
                    'member_lookup' => $member->user->full_name.' is not currently checked in.',
                ]);
            }

            $activeAttendance->update(['check_out_time' => now()]);

            return redirect()
                ->route('staff.checkin')
                ->with('staff_notice', $member->user->full_name.' has been checked out successfully.');
        }

        if ($activeAttendance) {
            return back()->withInput()->withErrors([
                'member_lookup' => $member->user->full_name.' is already checked in. Use Check-out instead.',
            ]);
        }

        $class = $validated['class_id']
            ? GymClass::query()->find($validated['class_id'])
            : $this->resolveDefaultClassForCheckin();

        if (! $class) {
            return back()->withInput()->withErrors([
                'member_lookup' => 'No class session is available to attach this check-in to yet.',
            ]);
        }

        Attendance::query()->create([
            'member_id' => $member->member_id,
            'class_id' => $class->class_id,
            'check_in_time' => now(),
            'status' => 'Present',
        ]);

        return redirect()
            ->route('staff.checkin')
            ->with('staff_notice', $member->user->full_name.' checked in under '.$class->class_name.'.');
    }

    public function classes(): View
    {
        $staff = $this->currentStaff();

        return view('staff.classes', [
            'classes' => $this->upcomingClassesCollection($staff?->staff_id),
            'staffMember' => $staff,
            'trainers' => $this->trainerOptions(),
        ]);
    }

    public function assignTrainer(Request $request, GymClass $gymClass): RedirectResponse
    {
        $validated = $request->validate([
            'trainer_id' => ['required', 'integer', 'exists:staff,staff_id'],
        ]);

        $scheduleTime = $gymClass->schedule_time ? Carbon::parse($gymClass->schedule_time) : null;
        if ($scheduleTime && $scheduleTime->copy()->addHour()->isPast()) {
            return redirect()
                ->route('staff.classes')
                ->withErrors(['trainer_assignment' => 'Completed classes can no longer receive trainer assignments.']);
        }

        $trainer = Staff::query()
            ->with('user')
            ->findOrFail($validated['trainer_id']);

        $alreadyAssigned = $gymClass->trainers()
            ->where('staff.staff_id', $trainer->staff_id)
            ->exists();

        if ($alreadyAssigned) {
            return redirect()
                ->route('staff.classes')
                ->with('staff_notice', ($trainer->user?->full_name ?? 'Selected trainer').' is already assigned to '.$gymClass->class_name.'.');
        }

        $classStart = $scheduleTime ?? Carbon::parse($gymClass->schedule_time);
        $classEnd = $classStart->copy()->addHour();

        $hasScheduleConflict = DB::table('class_trainers')
            ->join('classes', 'classes.class_id', '=', 'class_trainers.class_id')
            ->where('class_trainers.staff_id', $trainer->staff_id)
            ->where('classes.class_id', '!=', $gymClass->class_id)
            ->where(function ($query) use ($classStart, $classEnd): void {
                $query
                    ->whereBetween('classes.schedule_time', [$classStart, $classEnd->copy()->subSecond()])
                    ->orWhere(function ($overlapQuery) use ($classStart, $classEnd): void {
                        $overlapQuery
                            ->where('classes.schedule_time', '<', $classStart)
                            ->whereRaw('DATE_ADD(classes.schedule_time, INTERVAL 1 HOUR) > ?', [$classStart]);
                    });
            })
            ->exists();

        if ($hasScheduleConflict) {
            return redirect()
                ->route('staff.classes')
                ->withErrors([
                    'trainer_assignment' => ($trainer->user?->full_name ?? 'Selected trainer').' is already assigned to another class during this schedule.',
                ]);
        }

        $gymClass->trainers()->syncWithoutDetaching([$trainer->staff_id]);

        return redirect()
            ->route('staff.classes')
            ->with('staff_notice', ($trainer->user?->full_name ?? 'Selected trainer').' has been assigned to '.$gymClass->class_name.'.');
    }

    public function members(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $members = DB::table('user_overview_view')
            ->where('user_role', 'Member')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('member_id', $search)
                        ->orWhere('membership_type', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('member_id')
            ->get()
            ->map(function (object $member): object {
                $member->last_visit_at = $member->last_visit_at ? Carbon::parse($member->last_visit_at) : null;

                return $member;
            });

        return view('staff.members', [
            'members' => $members,
            'search' => $search,
        ]);
    }

    public function equipment(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $equipment = DB::table('equipment_with_classes_view')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('condition_status', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get()
            ->map(function (object $item): object {
                $item->last_maintenance_date = $item->last_maintenance_date
                    ? Carbon::parse($item->last_maintenance_date)
                    : null;

                return $item;
            });

        $issues = (int) ($this->scalar('SELECT get_equipment_issues_count() AS value') ?? 0);

        return view('staff.equipment', [
            'equipment' => $equipment,
            'search' => $search,
            'issues' => $issues,
        ]);
    }

    private function checkInStats(): array
    {
        $todayAttendance = (int) ($this->scalar('SELECT get_today_attendance_count() AS value') ?? 0);
        $currentlyIn = (int) ($this->scalar('SELECT get_currently_in_count() AS value') ?? 0);

        return [
            'total' => $todayAttendance,
            'currently_in' => $currentlyIn,
            'checked_out' => max(0, $todayAttendance - $currentlyIn),
        ];
    }

    private function todaysClassesCollection(): Collection
    {
        $now = now();

        return DB::table('classes_with_bookings_view')
            ->whereDate('schedule_time', today())
            ->orderBy('schedule_time')
            ->get()
            ->map(function (object $class) use ($now) {
                $class->schedule_time = $class->schedule_time ? Carbon::parse($class->schedule_time) : null;
                $start = $class->schedule_time;
                $end = $start->copy()->addHour();

                if ($now->between($start, $end)) {
                    $scheduleState = 'In Progress';
                } elseif ($now->gt($end)) {
                    $scheduleState = 'Completed';
                } else {
                    $scheduleState = 'Upcoming';
                }

                $class->schedule_state = $scheduleState;
                $class->booked_slots_count = (int) ($class->bookings_count ?? 0);
                $class->utilization = $class->max_slots > 0
                    ? (int) round(($class->booked_slots_count / $class->max_slots) * 100)
                    : 0;

                return $class;
            });
    }

    private function upcomingClassesCollection(?int $staffId = null): Collection
    {
        $now = now();
        $assignedClassIds = $staffId
            ? DB::table('class_trainers')
                ->where('staff_id', $staffId)
                ->pluck('class_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];
        $trainerIdsByClass = DB::table('class_trainers')
            ->select('class_id', DB::raw("GROUP_CONCAT(staff_id ORDER BY staff_id SEPARATOR ',') AS trainer_ids_csv"))
            ->groupBy('class_id')
            ->pluck('trainer_ids_csv', 'class_id');

        return DB::table('classes_with_bookings_view')
            ->where('schedule_time', '>=', $now->copy()->subHour())
            ->orderBy('schedule_time')
            ->get()
            ->map(function (object $class) use ($now, $assignedClassIds, $trainerIdsByClass) {
                $class->schedule_time = $class->schedule_time ? Carbon::parse($class->schedule_time) : null;
                $start = $class->schedule_time;
                $end = $start->copy()->addHour();

                if ($now->between($start, $end)) {
                    $scheduleState = 'In Progress';
                } elseif ($now->gt($end)) {
                    $scheduleState = 'Completed';
                } else {
                    $scheduleState = 'Upcoming';
                }

                $class->schedule_state = $scheduleState;
                $class->booked_slots_count = (int) ($class->bookings_count ?? 0);
                $class->utilization = $class->max_slots > 0
                    ? (int) round(($class->booked_slots_count / $class->max_slots) * 100)
                    : 0;
                $class->assigned_to_current_staff = in_array((int) $class->class_id, $assignedClassIds, true);
                $class->trainer_ids_csv = (string) ($trainerIdsByClass[$class->class_id] ?? '');

                return $class;
            })
            ->values();
    }

    private function currentStaff(): ?Staff
    {
        return auth()->user()?->staff;
    }

    private function trainerOptions(): Collection
    {
        return Staff::query()
            ->with('user')
            ->whereHas('user', function ($query): void {
                $query->whereNull('deleted_at')
                    ->where('status', 'Active');
            })
            ->get()
            ->sortBy(fn (Staff $staff) => $staff->user?->full_name ?? '')
            ->values();
    }

    private function findMember(string $lookup): ?Member
    {
        return Member::query()
            ->with('user')
            ->where('member_id', $lookup)
            ->orWhereHas('user', function ($query) use ($lookup): void {
                $query->where('email', $lookup)
                    ->orWhere('full_name', 'like', "%{$lookup}%");
            })
            ->first();
    }

    private function resolveDefaultClassForCheckin(): ?GymClass
    {
        return GymClass::query()
            ->whereDate('schedule_time', today())
            ->orderBy('schedule_time')
            ->first()
            ?? GymClass::query()->orderByDesc('schedule_time')->first();
    }

    private function attendanceForDate(string $date): Collection
    {
        return collect(DB::select('CALL get_attendances_by_date(?)', [$date]))
            ->map(function (object $attendance): object {
                $attendance->check_in_time = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
                $attendance->check_out_time = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

                return $attendance;
            });
    }

    private function scalar(string $sql, array $bindings = []): mixed
    {
        return DB::selectOne($sql, $bindings)?->value;
    }
}
