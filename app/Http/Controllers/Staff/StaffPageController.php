<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Equipment;
use App\Models\Staff;
use App\Models\User;
use App\Support\DatabaseMetrics;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
            'todaysClassesCount' => DatabaseMetrics::classesTodayCount(),
            'todaysSchedule' => $classes->take(5),
            'recentCheckIns' => $this->attendanceForDate(today()->toDateString())->take(5),
        ]);
    }

    public function checkin(Request $request): View
    {
        $lookup = trim((string) $request->query('lookup'));
        $memberPreview = $lookup !== '' ? $this->findMember($lookup) : null;

        // Get all active members (currently in gym)
        $activeMembersData = collect(DB::select('
            SELECT 
                m.member_id,
                u.full_name,
                m.membership_type,
                a.attendance_id,
                a.check_in_time,
                c.class_name
            FROM attendances a
            JOIN members m ON a.member_id = m.member_id
            JOIN users u ON m.user_id = u.id
            LEFT JOIN classes c ON a.class_id = c.class_id
            WHERE DATE(a.check_in_time) = CURDATE()
            AND a.check_out_time IS NULL
            ORDER BY a.check_in_time DESC
        '))->map(function (object $item): object {
            $item->check_in_time = $item->check_in_time ? Carbon::parse($item->check_in_time) : null;
            return $item;
        });

        // Get all active members for dropdown
        $allMembers = collect(DB::select('
            SELECT 
                m.member_id,
                u.full_name,
                m.membership_type,
                m.status,
                u.status as user_status
            FROM members m
            JOIN users u ON m.user_id = u.id
            WHERE m.status = "Active" AND u.status = "Active"
            ORDER BY u.full_name ASC
        '))->map(function (object $item): object {
            return $item;
        });

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
            'activeMembers' => $activeMembersData,
            'allMembers' => $allMembers,
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

    public function quickCheckout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendance_id' => ['required', 'exists:attendances,attendance_id'],
        ]);

        $attendance = Attendance::with(['member.user'])->findOrFail($validated['attendance_id']);

        if ($attendance->check_out_time) {
            return response()->json([
                'message' => $attendance->member->user->full_name.' is already checked out.',
            ], 422);
        }

        $attendance->update(['check_out_time' => now()]);

        return response()->json([
            'message' => $attendance->member->user->full_name.' has been checked out successfully.',
            'success' => true,
        ]);
    }

    public function classes(): View
    {
        $staff = $this->currentStaff();
        $classes = $this->upcomingClassesCollection($staff?->staff_id);

        return view('staff.classes', [
            'classes' => $this->paginateCollection($classes, 12, 'classes_page'),
            'staffMember' => $staff,
            'trainers' => $this->trainerOptions(),
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

        return redirect()
            ->route('staff.classes')
            ->with('success', 'New class created. Assign an instructor before it becomes visible to members.');
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

        $hasScheduleConflict = DB::table('class_trainer')
            ->join('classes', 'classes.class_id', '=', 'class_trainer.class_id')
            ->where('class_trainer.staff_id', $trainer->staff_id)
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

        $members = User::withOverview()
            ->where('users.role', 'Member')
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
            ->paginate(15)
            ->through(function (object $member): object {
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

        $equipment = Equipment::withClasses()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->through(function (object $item): object {
                $item->last_maintenance_date = $item->last_maintenance_date
                    ? Carbon::parse($item->last_maintenance_date)
                    : null;

                return $item;
            });

        $issues = DatabaseMetrics::equipmentIssuesCount();

        return view('staff.equipment', [
            'equipment' => $equipment,
            'search' => $search,
            'issues' => $issues,
        ]);
    }

    private function checkInStats(): array
    {
        $todayAttendance = DatabaseMetrics::todayAttendanceCount();
        $currentlyIn = DatabaseMetrics::currentlyInCount();

        return [
            'total' => $todayAttendance,
            'currently_in' => $currentlyIn,
            'checked_out' => max(0, $todayAttendance - $currentlyIn),
        ];
    }

    private function todaysClassesCollection(): Collection
    {
        $now = now();

        return GymClass::withBookings()
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
            ? DB::table('class_trainer')
                ->where('staff_id', $staffId)
                ->pluck('class_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];
        $trainerIdsByClass = DB::table('class_trainer')
            ->select('class_id', DB::raw("GROUP_CONCAT(staff_id ORDER BY staff_id SEPARATOR ',') AS trainer_ids_csv"))
            ->groupBy('class_id')
            ->pluck('trainer_ids_csv', 'class_id');

        return GymClass::withBookings()
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

    public function storeEquipment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|string|in:Available,Under Repair,Maintenance',
            'description' => 'nullable|string|max:500',
        ]);

        Equipment::create($validated);

        return redirect()->route('staff.equipment')->with('success', 'Equipment item added successfully.');
    }

    private function currentStaff(): ?Staff
    {
        /** @var User|null $user */
        $user = request()->user();

        return $user?->staff;
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
        return Attendance::forDateWithDetails($date)
            ->get()
            ->map(function (object $attendance): object {
                $attendance->check_in_time = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
                $attendance->check_out_time = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

                return $attendance;
            });
    }
}
