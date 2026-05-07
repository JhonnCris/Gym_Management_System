@extends('layouts.staff', ['title' => 'Classes'])

@section('content')
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Schedule control</span>
            <h1 class="page-title">Upcoming Classes</h1>
            <p class="page-subtitle">Track upcoming sessions, watch capacity, and know which classes need attention before the room opens.</p>
        </div>
    </section>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if (session('staff_notice'))
        <div class="alert success">{{ session('staff_notice') }}</div>
    @endif

    @if ($errors->has('trainer_assignment') || $errors->has('trainer_id'))
        <div class="alert danger">{{ $errors->first('trainer_assignment') ?: $errors->first('trainer_id') }}</div>
    @endif

    <section class="section-card">
        <div class="section-heading">
            <strong>Add New Class</strong>
        </div>

        <form method="POST" action="{{ route('staff.classes.store') }}" class="field-grid">
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

            <div class="field">
                <button type="submit" class="btn primary">Create class</button>
            </div>
        </form>

        <style>
            .field-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
                margin-top: 16px;
            }
            .field-grid .field {
                display: flex;
                flex-direction: column;
            }
            .field-grid .field label {
                margin-bottom: 4px;
                font-weight: 600;
            }
            .field-grid .field input {
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            .field-grid .field input.input-invalid {
                border-color: #dc3545;
            }
            .field-grid .field .field-error {
                color: #dc3545;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
            .field-grid .field:last-child {
                grid-column: span 3;
                display: flex;
                justify-content: flex-end;
                align-items: flex-end;
            }
        </style>
    </section>

    <section class="class-stack">
        @forelse ($classes as $class)
            @php
                $assignedTrainerIds = collect(explode(',', (string) ($class->trainer_ids_csv ?? '')))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->all();
            @endphp
            <article class="panel-card class-card">
                <div class="class-main">
                    <div class="class-header-line">
                        <div>
                            <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'calendar'])</span>{{ $class->class_name }}</h2>
                            <div class="class-meta">
                                <span>{{ $class->trainer_names ?: 'Trainer pending' }}</span>
                                <span>{{ $class->schedule_time->format('h:i A') }} - {{ $class->schedule_time->copy()->addHour()->format('h:i A') }}</span>
                            </div>
                        </div>
                        <span class="badge {{ str($class->schedule_state)->slug('-') }}">{{ $class->schedule_state }}</span>
                    </div>
                </div>

                <div class="class-side">
                    <strong>{{ $class->booked_slots_count }}/{{ $class->max_slots }}</strong>
                    <span>Enrollment</span>
                    <div class="progress">
                        <span @style(['width: '.min($class->utilization, 100).'%'])></span>
                    </div>
                    @if ($class->trainer_names)
                        <button type="button" class="btn-secondary" disabled>
                            Assigned to {{ strtok($class->trainer_names, ',') }}
                        </button>
                    @elseif ($trainers->isNotEmpty())
                        <details class="trainer-assignment">
                            <summary class="btn-primary">Assign trainer</summary>
                            <form method="POST" action="{{ route('staff.classes.assign-trainer', $class->class_id) }}" class="trainer-assignment-form">
                                @csrf
                                <label class="trainer-assignment-label" for="trainer_{{ $class->class_id }}">Available trainers</label>
                                <select id="trainer_{{ $class->class_id }}" name="trainer_id" class="staff-input" required>
                                    <option value="">Select a trainer</option>
                                    @foreach ($trainers as $trainer)
                                        <option value="{{ $trainer->staff_id }}">
                                            {{ $trainer->user?->full_name ?? ('Trainer #'.$trainer->staff_id) }}{{ $trainer->specialization ? ' - '.$trainer->specialization : '' }}{{ in_array($trainer->staff_id, $assignedTrainerIds, true) ? ' (Assigned)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-secondary">Save trainer</button>
                            </form>
                        </details>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel-card empty-state">No upcoming classes are available for trainer assignment right now.</div>
        @endforelse
        @if ($classes->hasPages())
            <div class="pagination">
                {{ $classes->links() }}
            </div>
        @endif
    </section>
@endsection
