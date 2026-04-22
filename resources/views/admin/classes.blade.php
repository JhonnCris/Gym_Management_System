@extends('layouts.admin', ['title' => 'Class Management'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Class Management</h1>
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
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'plus'])</span>
            <strong>Add New Class</strong>
        </div>

        @if (session('success'))
            <div class="field-error" style="color: #114d05; background: rgba(220, 248, 198, 0.45); border-radius: 16px; padding: 14px; margin-bottom: 18px; border: 1px solid rgba(52, 211, 153, 0.3);">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.classes.store') }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 16px;">
            @csrf

            <div class="field">
                <label for="class_name">Class name</label>
                <input id="class_name" name="class_name" type="text" value="{{ old('class_name') }}" class="{{ $errors->has('class_name') ? 'input-invalid' : '' }}">
                @error('class_name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="schedule_time">Schedule</label>
                <input id="schedule_time" name="schedule_time" type="datetime-local" value="{{ old('schedule_time') }}" class="{{ $errors->has('schedule_time') ? 'input-invalid' : '' }}">
                @error('schedule_time')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="max_slots">Max slots</label>
                <input id="max_slots" name="max_slots" type="number" min="1" value="{{ old('max_slots', 10) }}" class="{{ $errors->has('max_slots') ? 'input-invalid' : '' }}">
                @error('max_slots')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div style="grid-column: span 3; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn primary">Add class</button>
            </div>
        </form>
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
                        <tr>
                            <td>
                                <strong>{{ $class->class_name }}</strong>
                                <div class="table-subtle">{{ $class->max_slots }} max slots</div>
                            </td>
                            <td>{{ $class->schedule_time->format('M d, Y h:i A') }}</td>
                            <td>{{ $class->trainer_names ?: 'Trainer pending' }}</td>
                            <td>{{ $class->bookings_count }}/{{ $class->max_slots }}</td>
                            <td>
                                <div class="mini-progress">
                                    <span style="<?php echo 'width: '.min($class->fill_rate, 100).'%'; ?>"></span>
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
    </section>
@endsection
