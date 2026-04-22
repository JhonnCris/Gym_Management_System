@extends('layouts.member', ['title' => 'Dashboard', 'currentUser' => $user, 'currentMember' => $member])

@section('page-title', 'Welcome back, '.$user->full_name)
@section('page-subtitle', 'Your memberships, bookings, and latest activity in one clean view.')

@section('content')
    <section class="stat-grid">
        <article class="stat-card accent-blue">
            <div class="stat-icon">@include('member.partials.icon', ['name' => 'membership'])</div>
            <span class="stat-label">Membership</span>
            <strong class="stat-value">{{ $stats['membership'] }}</strong>
            <p class="stat-meta">Status: {{ $member->status ?? 'Active' }}</p>
        </article>
        <article class="stat-card accent-green">
            <div class="stat-icon blue">@include('member.partials.icon', ['name' => 'visits'])</div>
            <span class="stat-label">Visits this week</span>
            <strong class="stat-value">{{ $stats['visits_this_week'] }}</strong>
            <p class="stat-meta">Based on check-ins from this week</p>
        </article>
        <article class="stat-card accent-amber">
            <div class="stat-icon amber">@include('member.partials.icon', ['name' => 'classes'])</div>
            <span class="stat-label">Upcoming bookings</span>
            <strong class="stat-value">{{ $stats['booked_classes'] }}</strong>
            <p class="stat-meta">Classes currently on your schedule</p>
        </article>
        <article class="stat-card accent-green">
            <div class="stat-icon green">@include('member.partials.icon', ['name' => 'calendar'])</div>
            <span class="stat-label">Next renewal</span>
            <strong class="stat-value">{{ $stats['next_renewal'] }}</strong>
            <p class="stat-meta">Membership expiry or billing date</p>
        </article>
    </section>

    <section class="content-grid two-up">
        <article class="surface-card">
            <div class="card-head">
                <div>
                    <p class="card-kicker">Weekly activity</p>
                    <h2>This Week's Check-ins</h2>
                </div>
            </div>

            <div class="activity-chart">
                @foreach ($weeklyVisits as $day)
                    @php
                        $height = max(10, (int) round(($day['count'] / $weeklyVisitMax) * 100));
                    @endphp
                    <div class="activity-bar-group">
                        <div class="activity-bar-track">
                            <span class="activity-bar" <?php echo 'style="height: '.$height.'%;"'; ?> title="{{ $day['label'] }}: {{ $day['count'] }} visit(s)"></span>
                        </div>
                        <span class="activity-value">{{ $day['count'] }}</span>
                        <span class="activity-label">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="surface-card">
            <div class="card-head">
                <div>
                    <p class="card-kicker">Next on your schedule</p>
                    <h2>Upcoming Classes</h2>
                </div>
                <a href="{{ route('member.classes') }}" class="text-link">View all</a>
            </div>

            <div class="stack-list">
                @forelse ($upcomingBookings as $booking)
                    <div class="list-card">
                        <div>
                            <strong>{{ $booking->class_name ?? 'Class unavailable' }}</strong>
                            <p>{{ $booking->trainer_names ?: 'Trainer to be assigned' }}</p>
                        </div>
                        <div class="list-meta">
                            <span>{{ optional($booking->schedule_time)?->format('D, M d') ?? 'Schedule TBD' }}</span>
                            <span>{{ optional($booking->schedule_time)?->format('h:i A') ?? '--:--' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No upcoming class bookings yet. Browse the class schedule to reserve your next session.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="content-grid dashboard-bottom">
        <article class="surface-card compact-card">
            <div class="card-head">
                <div>
                    <p class="card-kicker">Membership snapshot</p>
                    <h2>Plan Details</h2>
                </div>
            </div>

            <dl class="info-grid">
                <div>
                    <dt>Member ID</dt>
                    <dd>M{{ str_pad((string) $member->member_id, 4, '0', STR_PAD_LEFT) }}</dd>
                </div>
                <div>
                    <dt>Member since</dt>
                    <dd>{{ optional($member->join_date)?->format('M d, Y') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt>Plan</dt>
                    <dd>{{ $member->membership_type ?? 'Standard' }}</dd>
                </div>
                <div>
                    <dt>Renewal</dt>
                    <dd>{{ optional($member->expiry_date)?->format('M d, Y') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt>Total visits</dt>
                    <dd>{{ $summary['total_visits'] }}</dd>
                </div>
                <div>
                    <dt>Paid invoices</dt>
                    <dd>{{ $summary['completed_payments'] }}</dd>
                </div>
            </dl>
        </article>

        <article class="surface-card compact-card">
            <div class="card-head">
                <div>
                    <p class="card-kicker">Billing summary</p>
                    <h2>Latest Payment</h2>
                </div>
                <a href="{{ route('member.payments') }}" class="text-link">Payment history</a>
            </div>

            @if ($latestPayment)
                <div class="payment-highlight">
                    <strong>PHP {{ number_format((float) $latestPayment->amount, 2) }}</strong>
                    <span>{{ $latestPayment->payment_method ?? 'Method unavailable' }}</span>
                    <span>{{ optional($latestPayment->payment_date)?->format('M d, Y - h:i A') ?? 'No payment date' }}</span>
                    <span class="status-badge {{ strtolower($latestPayment->status ?? 'paid') }}">{{ $latestPayment->status ?? 'Paid' }}</span>
                    <span class="summary-inline">Lifetime paid: PHP {{ number_format($summary['paid_total'], 2) }}</span>
                </div>
            @else
                <div class="empty-state">No payment records are available for this account yet.</div>
            @endif
        </article>
    </section>
@endsection
