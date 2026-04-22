@extends('layouts.staff', ['title' => 'Check-in'])

@section('content')
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Member access</span>
            <h1 class="page-title">Member Check-in</h1>
            <p class="page-subtitle">Use a member ID, email, or full name, then choose whether you want to check the member in or check them out.</p>
        </div>
    </section>

    @if (session('staff_notice'))
        <div class="alert success">{{ session('staff_notice') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert danger">{{ $errors->first() }}</div>
    @endif

    <section class="content-grid checkin-layout">
        <article class="panel-card checkin-card">
            <div class="checkin-illustration">
                <div class="avatar avatar-large">+</div>
            </div>

            <form method="POST" action="{{ route('staff.checkin.store') }}" class="checkin-form">
                @csrf
                <label class="field-label" for="member_lookup">Member ID or member details</label>
                <div class="search-field staff-search-field">
                    <span class="field-icon">@include('staff.partials.icon', ['name' => 'search'])</span>
                    <input id="member_lookup" name="member_lookup" class="staff-input" type="text" value="{{ old('member_lookup', request('lookup')) }}" placeholder="Ex. 15, juan@email.com, Juan Dela Cruz">
                </div>

                <label class="field-label" for="class_id">Attach to session</label>
                <select id="class_id" name="class_id" class="staff-input">
                    <option value="">Nearest active schedule</option>
                    @foreach ($todaysClassOptions as $classOption)
                        <option value="{{ $classOption->class_id }}" @selected(old('class_id') == $classOption->class_id)>
                            {{ $classOption->class_name }} - {{ $classOption->schedule_time->format('h:i A') }}
                        </option>
                    @endforeach
                </select>

                <div class="helper-text">Current system attendance is tied to a class session, so walk-in visits are attached to the closest schedule unless you choose one manually.</div>

                <div class="action-row">
                    <button type="submit" name="attendance_action" value="check_in" class="btn-primary">Check-in</button>
                    <button type="submit" name="attendance_action" value="check_out" class="btn-secondary">Check-out</button>
                </div>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-head">
                <div>
                    <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'status'])</span>Live snapshot</h2>
                    <p>Shift totals and member preview.</p>
                </div>
            </div>

            <div class="mini-stat-grid">
                <div class="mini-stat">
                    <span>Total check-ins</span>
                    <strong>{{ $stats['total'] }}</strong>
                </div>
                <div class="mini-stat">
                    <span>Currently in</span>
                    <strong>{{ $stats['currently_in'] }}</strong>
                </div>
                <div class="mini-stat">
                    <span>Checked out</span>
                    <strong>{{ $stats['checked_out'] }}</strong>
                </div>
            </div>

            @if ($memberPreview)
                <div class="member-preview">
                    <div class="avatar">{{ strtoupper(substr($memberPreview->user->full_name ?? 'M', 0, 1)) }}</div>
                    <div>
                        <strong>{{ $memberPreview->user->full_name }}</strong>
                        <span>#{{ $memberPreview->member_id }} - {{ $memberPreview->membership_type }}</span>
                        <span>{{ $memberPreview->user->email }}</span>
                        <span>{{ $memberPreviewAttendance ? 'Currently checked in' : 'Currently checked out' }}</span>
                    </div>
                    <span class="badge {{ $memberPreviewAttendance ? 'success' : 'inactive' }}">{{ $memberPreviewAttendance ? 'In Gym' : 'Checked Out' }}</span>
                </div>
            @else
                <div class="empty-state">Search for a member above to show their profile preview here.</div>
            @endif
        </article>
    </section>

    <section class="panel-card">
        <div class="panel-head">
            <div>
                <h2><span class="heading-icon">@include('staff.partials.icon', ['name' => 'clock'])</span>Recent check-ins</h2>
                <p>Most recent floor entries recorded today.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="staff-table">
                <thead>
                <tr>
                    <th>Member</th>
                    <th>Class</th>
                    <th>Checked In</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recentCheckIns as $attendance)
                    <tr>
                        <td>{{ $attendance->member_name ?? 'Unknown member' }}</td>
                        <td>{{ $attendance->class_name ?? 'No class' }}</td>
                        <td>{{ $attendance->check_in_time->format('h:i A') }}</td>
                        <td><span class="badge {{ $attendance->check_out_time ? 'inactive' : 'success' }}">{{ $attendance->check_out_time ? 'Checked Out' : 'In Gym' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-cell">No activity yet today.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
