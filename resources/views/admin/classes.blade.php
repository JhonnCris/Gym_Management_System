@extends('layouts.admin', ['title' => 'Class Monitoring'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Class Monitoring</h1>
            <p class="page-description">Track schedules, instructors, and booking pressure across your active programs.</p>
        </div>
    </div>

    <div class="summary-grid summary-grid-4">
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'classes'])</div>
            <p class="summary-title">Total Classes</p>
            <p class="summary-value">{{ $stats['total_classes'] }}</p>
            <small>{{ $stats['upcoming_classes'] }} upcoming schedules</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'attendance'])</div>
            <p class="summary-title">Bookings</p>
            <p class="summary-value">{{ $stats['bookings'] }}</p>
            <small>{{ $stats['todays_classes'] }} class{{ $stats['todays_classes'] === 1 ? '' : 'es' }} today</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'users'])</div>
            <p class="summary-title">Average Fill</p>
            <p class="summary-value">{{ $stats['average_fill_rate'] }}%</p>
            <small>Across all tracked sessions</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'warning'])</div>
            <p class="summary-title">Near Capacity</p>
            <p class="summary-value">{{ $stats['near_capacity'] }}</p>
            <small>Classes at 80% capacity or higher</small>
        </div>
    </div>

    <section class="section-card">
        <div class="section-heading">
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'eye'])</span>
            <strong>Class Monitoring</strong>
        </div>
        <p class="section-description">Admins may monitor schedules, capacity, and instructor assignments here. Class creation and instructor assignment are handled by staff members, and only classes with assigned instructors become visible to members.</p>
    </section>

    <section class="section-card">
        <div class="section-heading">
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'classes'])</span>
            <strong>Class Schedule Overview</strong>
        </div>
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Schedule</th>
                        <th>Trainer</th>
                        <th>Enrolled</th>
                        <th>Utilization</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $class)
                            <td>
                                <strong>{{ $class->class_name }}</strong>
                                <div class="table-subtle">{{ $class->max_slots }} max slots</div>
                            </td>
                            <td>{{ $class->schedule_time->format('M d, Y h:i A') }}</td>
                            <td>{{ $class->trainer_names ?: 'Trainer pending' }}</td>
                            <td>{{ $class->bookings_count }}/{{ $class->max_slots }}</td>
                            <td>
                                @php $fillWidth = min($class->fill_rate, 100); @endphp
                                <div class="mini-progress">
                                    <span style="--fill-width: {{ $fillWidth }}%"></span>
                                </div>
                                <div class="table-subtle">{{ $class->fill_rate }}% filled</div>
                            </td>
                            <td><span class="pill {{ str($class->schedule_state)->slug('-') }}">{{ $class->schedule_state }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No class schedules available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($classes->hasPages())
            <div class="pagination">
                {{ $classes->links() }}
            </div>
        @endif
    </section>
@endsection
