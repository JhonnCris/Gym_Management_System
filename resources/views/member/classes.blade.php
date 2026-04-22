@extends('layouts.member', ['title' => 'Classes', 'currentUser' => $user, 'currentMember' => $member])

@section('page-title', 'My Classes')
@section('page-subtitle', 'See only your upcoming booked classes and the future sessions you can still reserve.')

@section('content')
    <section class="stat-grid">
        <article class="stat-card accent-blue">
            <div class="stat-icon">@include('member.partials.icon', ['name' => 'classes'])</div>
            <span class="stat-label">Booked classes</span>
            <strong class="stat-value">{{ $classSummary['booked'] }}</strong>
            <p class="stat-meta">Upcoming reservations linked to your account</p>
        </article>
        <article class="stat-card accent-green">
            <div class="stat-icon green">@include('member.partials.icon', ['name' => 'calendar'])</div>
            <span class="stat-label">Available sessions</span>
            <strong class="stat-value">{{ $classSummary['available'] }}</strong>
            <p class="stat-meta">Future class schedules currently open in the portal</p>
        </article>
        <article class="stat-card accent-amber">
            <div class="stat-icon amber">@include('member.partials.icon', ['name' => 'clock'])</div>
            <span class="stat-label">Next booked class</span>
            <strong class="stat-value stat-value-sm">{{ $classSummary['nextClass'] }}</strong>
            <p class="stat-meta">Your nearest confirmed booking schedule</p>
        </article>
        <article class="stat-card accent-blue">
            <div class="stat-icon">@include('member.partials.icon', ['name' => 'check'])</div>
            <span class="stat-label">Booking limit</span>
            <strong class="stat-value">
                @if ($bookingLimit === null)
                    Unlimited
                @else
                    {{ $bookingLimit }} class{{ $bookingLimit === 1 ? '' : 'es' }}
                @endif
            </strong>
            <p class="stat-meta">{{ $member->membership_type === 'VIP' ? 'Unlimited class booking for VIP members' : ($member->membership_type === 'Premium' ? 'Up to 10 active bookings for Premium members' : 'Only 1 active booking allowed with Basic membership') }}</p>
        </article>
        <article class="stat-card accent-green">
            <div class="stat-icon green">@include('member.partials.icon', ['name' => 'calendar'])</div>
            <span class="stat-label">Class history</span>
            <strong class="stat-value">{{ $classSummary['history'] }}</strong>
            <p class="stat-meta">Past and completed booking records from your account</p>
        </article>
    </section>

    <section class="surface-card">
        <div class="card-head">
            <div>
                <p class="card-kicker">Reserved by you</p>
                <h2>Booked Classes</h2>
            </div>
        </div>

        <div class="booking-grid">
            @forelse ($bookedClasses as $booking)
                <article class="booking-card">
                    <div class="booking-head">
                        <div>
                            <strong>{{ $booking->class_name ?? 'Class unavailable' }}</strong>
                            <p>{{ $booking->trainer_names ?: 'Trainer to be assigned' }}</p>
                        </div>
                        <span class="status-badge active">Booked</span>
                    </div>
                    <div class="booking-meta">
                        <span>@include('member.partials.icon', ['name' => 'calendar']) {{ optional($booking->schedule_time)?->format('D, M d, Y') ?? 'Schedule TBD' }}</span>
                        <span>@include('member.partials.icon', ['name' => 'clock']) {{ optional($booking->schedule_time)?->format('h:i A') ?? '--:--' }}</span>
                    </div>
                    <button
                        type="button"
                        class="btn subtle member-booking-action"
                        data-action="cancel"
                        data-booking-id="{{ $booking->booking_id }}"
                        data-class-name="{{ $booking->class_name ?? 'Class unavailable' }}"
                        data-schedule="{{ optional($booking->schedule_time)?->format('D, M d, Y h:i A') ?? 'Schedule TBD' }}"
                        data-trainer="{{ $booking->trainer_names ?: 'Trainer to be assigned' }}">
                        Cancel booking
                    </button>
                </article>
            @empty
                <div class="empty-state">You do not have any upcoming booked classes yet.</div>
            @endforelse
        </div>
    </section>

    <section class="surface-card">
        <div class="card-head">
            <div>
                <p class="card-kicker">Full class catalogue</p>
                <h2>Available Classes</h2>
            </div>
        </div>

        <div class="class-list">
                @forelse ($availableClasses as $class)
                @php
                    $isBooked = in_array($class->class_id, $bookedClassIds, true);
                    $isFull = $class->bookings_count >= $class->max_slots;
                    $remaining = max($class->max_slots - $class->bookings_count, 0);
                @endphp
                <article class="class-row">
                    <div class="class-main">
                        <div class="class-title-row">
                            <h3>{{ $class->class_name }}</h3>
                            @if ($isBooked)
                                <span class="pill info">Already booked</span>
                            @elseif ($isFull)
                                <span class="pill danger">Class full</span>
                            @elseif ($hasReachedBookingLimit)
                                <span class="pill warning">Booking limit reached</span>
                            @else
                                <span class="pill success">{{ $remaining }} spots left</span>
                            @endif
                        </div>
                        <div class="class-meta">
                            <span>Coach: {{ $class->trainer_names ?: 'Trainer to be assigned' }}</span>
                            <span>{{ optional($class->schedule_time)?->format('D, M d, Y') ?? 'Date TBD' }}</span>
                            <span>{{ optional($class->schedule_time)?->format('h:i A') ?? '--:--' }}</span>
                            <span>{{ $class->bookings_count }}/{{ $class->max_slots }} enrolled</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn {{ ($isBooked || $isFull || $hasReachedBookingLimit) ? 'subtle' : 'primary' }} member-booking-action"
                        data-action="book"
                        data-class-id="{{ $class->class_id }}"
                        {{ ($isBooked || $isFull || $hasReachedBookingLimit) ? 'disabled' : '' }}>
                        {{ $isBooked ? 'Booked' : ($isFull ? 'Full' : ($hasReachedBookingLimit ? 'Limit reached' : 'Book Class')) }}
                    </button>
                </article>
            @empty
                <div class="empty-state">No future classes are currently open for booking.</div>
            @endforelse
        </div>
    </section>

    <section class="surface-card">
        <div class="card-head">
            <div>
                <p class="card-kicker">Previous records</p>
                <h2>Class History</h2>
            </div>
        </div>

        <div class="class-list">
            @forelse ($bookingHistory as $booking)
                <article class="class-row">
                    <div class="class-main">
                        <div class="class-title-row">
                            <h3>{{ $booking->class_name ?? 'Class unavailable' }}</h3>
                            <span class="pill {{ strtolower($booking->status ?? 'info') === 'completed' ? 'success' : (strtolower($booking->status ?? '') === 'cancelled' ? 'danger' : 'info') }}">
                                {{ $booking->status ?? 'Recorded' }}
                            </span>
                        </div>
                        <div class="class-meta">
                            <span>Coach: {{ $booking->trainer_names ?: 'Trainer to be assigned' }}</span>
                            <span>{{ optional($booking->schedule_time)?->format('D, M d, Y') ?? 'Date TBD' }}</span>
                            <span>{{ optional($booking->schedule_time)?->format('h:i A') ?? '--:--' }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn subtle" disabled>Record saved</button>
                </article>
            @empty
                <div class="empty-state">No previous class records found for this member yet.</div>
            @endforelse
        </div>
    </section>

    <div class="member-toast" id="memberClassToast" hidden></div>

    <div class="payment-modal-backdrop" id="cancelBookingModal" hidden>
        <div class="payment-modal-card" role="dialog" aria-modal="true" aria-labelledby="cancelBookingTitle">
            <div class="payment-modal-head">
                <div>
                    <h3 id="cancelBookingTitle">Cancel booked class</h3>
                    <p>Tell us why you're cancelling and confirm your booking details before we process the request.</p>
                </div>
                <button type="button" class="modal-close-btn" id="cancelBookingModalClose" aria-label="Close cancellation modal">×</button>
            </div>

            <div class="payment-modal-summary">
                <div>
                    <span class="detail-label">Class</span>
                    <strong id="cancelBookingClassName">—</strong>
                </div>
                <div>
                    <span class="detail-label">Scheduled</span>
                    <strong id="cancelBookingSchedule">—</strong>
                    <span id="cancelBookingTrainer" class="detail-meta">Trainer unassigned</span>
                </div>
            </div>

            <div>
                <label for="cancelBookingReason" class="detail-label">Cancellation reason</label>
                <textarea id="cancelBookingReason" rows="4" placeholder="Share why you are cancelling this class." class="cancel-reason-textarea"></textarea>
            </div>

            <p class="payment-inline-error" id="cancelBookingModalError" hidden></p>

            <div class="payment-modal-actions">
                <button type="button" class="btn" id="cancelBookingModalCancel">Back</button>
                <button type="button" class="btn danger" id="cancelBookingModalConfirm">Confirm cancellation</button>
            </div>
        </div>
    </div>

    <div id="memberClassesConfig" data-member-id="{{ $member->member_id }}" hidden></div>
@endsection

@section('scripts')
    <script>
        const configElement = document.getElementById('memberClassesConfig');
        window.memberClassesConfig = configElement
            ? { memberId: Number(configElement.dataset.memberId) }
            : null;
    </script>
    <script src="{{ asset('js/member-classes.js') }}"></script>
@endsection
