@extends('layouts.staff', ['title' => 'Classes'])

@section('content')
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Schedule control</span>
            <h1 class="page-title">Upcoming Classes</h1>
            <p class="page-subtitle">Track upcoming sessions, watch capacity, and know which classes need attention before the room opens.</p>
        </div>
    </section>

    @if (session('staff_notice'))
        <div class="alert success">{{ session('staff_notice') }}</div>
    @endif

    @if ($errors->has('trainer_assignment') || $errors->has('trainer_id'))
        <div class="alert danger">{{ $errors->first('trainer_assignment') ?: $errors->first('trainer_id') }}</div>
    @endif

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
    </section>
@endsection
