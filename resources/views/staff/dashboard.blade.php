@extends('layouts.staff', ['title' => 'Dashboard'])

@section('content')
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Front desk overview</span>
            <h1 class="page-title">Staff Dashboard</h1>
            <p class="page-subtitle">See check-ins, manage today's floor activity, and keep your shift moving without jumping between screens.</p>
        </div>
        <div class="hero-badge">
            <span>Today</span>
            <strong>{{ now()->format('l, M d') }}</strong>
        </div>
    </section>

    <section class="stat-grid">
        <article class="stat-card accent-blue">
            <div class="card-icon">@include('staff.partials.icon', ['name' => 'members'])</div>
            <span class="stat-label">Today's Attendance</span>
            <strong class="stat-value">{{ $stats['total'] }}</strong>
            <small>Total successful check-ins logged</small>
        </article>
        <article class="stat-card accent-green">
            <div class="card-icon">@include('staff.partials.icon', ['name' => 'status'])</div>
            <span class="stat-label">Currently in Gym</span>
            <strong class="stat-value">{{ $stats['currently_in'] }}</strong>
            <small>Members without checkout yet</small>
        </article>
        <article class="stat-card accent-amber">
            <div class="card-icon">@include('staff.partials.icon', ['name' => 'calendar'])</div>
            <span class="stat-label">Today's Classes</span>
            <strong class="stat-value">{{ $todaysClassesCount }}</strong>
            <small>Sessions scheduled for the day</small>
        </article>
    </section>

    <section class="content-grid two-up">
        <article class="panel-card">
            <div class="panel-head">
                <div>
                    <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'calendar'])</span>Today's schedule</h2>
                    <p>Quick scan of sessions happening on the floor.</p>
                </div>
                <a href="{{ route('staff.classes') }}" class="text-link">View all</a>
            </div>

            <div class="stack-list">
                @forelse ($todaysSchedule as $class)
                    <div class="schedule-row">
                        <div>
                            <strong>{{ $class->class_name }}</strong>
                            <span>{{ $class->schedule_time->format('h:i A') }} • {{ $class->trainer_names ?: 'Staff trainer pending' }}</span>
                        </div>
                        <span class="badge {{ str($class->schedule_state)->slug('-') }}">{{ $class->schedule_state }}</span>
                    </div>
                @empty
                    <div class="empty-state">No class schedules have been added for today.</div>
                @endforelse
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-head">
                <div>
                    <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'clock'])</span>Recent member movement</h2>
                    <p>Latest check-ins recorded this shift.</p>
                </div>
                <a href="{{ route('staff.checkin') }}" class="text-link">Open check-in</a>
            </div>

            <div class="member-feed">
                @forelse ($recentCheckIns as $attendance)
                    <div class="feed-row">
                        <div class="avatar">{{ strtoupper(substr($attendance->member_name ?? 'M', 0, 1)) }}</div>
                        <div>
                            <strong>{{ $attendance->member_name ?? 'Unknown member' }}</strong>
                            <span>Checked in at {{ $attendance->check_in_time->format('h:i A') }}</span>
                        </div>
                        <span class="badge {{ $attendance->check_out_time ? 'inactive' : 'success' }}">{{ $attendance->check_out_time ? 'Checked Out' : 'In Gym' }}</span>
                    </div>
                @empty
                    <div class="empty-state">No check-ins logged yet today.</div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
