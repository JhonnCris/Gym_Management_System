@extends('layouts.admin', ['title' => 'Attendance Management'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Attendance Management</h1>
            <p class="page-description">Monitor daily floor activity, current in-gym members, and recent check-in history.</p>
        </div>
    </div>

    <div class="summary-grid summary-grid-4">
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'attendance'])</div>
            <p class="summary-title">Today Check-ins</p>
            <p class="summary-value">{{ $stats['today_total'] }}</p>
            <small>{{ $stats['today_unique_members'] }} unique members</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'active'])</div>
            <p class="summary-title">Currently In Gym</p>
            <p class="summary-value">{{ $stats['currently_in'] }}</p>
            <small>Open attendance sessions</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'reports'])</div>
            <p class="summary-title">This Week</p>
            <p class="summary-value">{{ $stats['week_total'] }}</p>
            <small>Total attendance records</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'classes'])</div>
            <p class="summary-title">Attached Classes</p>
            <p class="summary-value">{{ $stats['classes_touched'] }}</p>
            <small>Classes with attendance this week</small>
        </div>
    </div>

    <section class="section-card">
        <div class="section-heading">
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'attendance'])</span>
            <strong>Recent Attendance Log</strong>
        </div>
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Class</th>
                        <th>Checked In</th>
                        <th>Checked Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendanceRecords as $attendance)
                        <tr>
                            <td>
                                <strong>{{ $attendance->member_name ?? 'Unknown member' }}</strong>
                                <div class="table-subtle">#{{ $attendance->member_id }}</div>
                            </td>
                            <td>{{ $attendance->class_name ?? 'No class attached' }}</td>
                            <td>{{ optional($attendance->check_in_time)?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                            <td>{{ optional($attendance->check_out_time)?->format('M d, Y h:i A') ?? 'Still active' }}</td>
                            <td><span class="pill {{ $attendance->check_out_time ? 'checked-out' : 'in-gym' }}">{{ $attendance->check_out_time ? 'Checked Out' : 'In Gym' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No attendance records available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendanceRecords->hasPages())
            <div class="pagination">
                {{ $attendanceRecords->links() }}
            </div>
        @endif
    </section>
@endsection
