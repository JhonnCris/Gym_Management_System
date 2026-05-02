@extends('layouts.member', ['title' => 'My Profile', 'currentUser' => $user, 'currentMember' => $member])

@section('page-title', 'My Profile')
@section('page-subtitle', 'Personal details, membership information, and recent activity in a consistent member record view.')

@section('content')
    <section class="content-grid profile-grid">
        <article class="surface-card profile-identity">
            <div class="profile-avatar">{{ strtoupper(substr($user->full_name ?? 'M', 0, 1)) }}</div>
            <h2>{{ $user->full_name }}</h2>
            <p class="profile-id">Member ID M{{ str_pad((string) $member->member_id, 4, '0', STR_PAD_LEFT) }}</p>
            <span class="status-badge {{ strtolower($member->status ?? 'active') }}">{{ $member->status ?? 'Active' }}</span>

            <dl class="info-grid single">
                <div>
                    <dt>Member since</dt>
                    <dd>{{ optional($member->join_date)?->format('M d, Y') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt>Membership plan</dt>
                    <dd>{{ $member->membershipPlan?->name ?? $member->membership_type ?? 'Standard' }}</dd>
                </div>
                <div>
                    <dt>Current bookings</dt>
                    <dd>{{ $bookingsCount }}</dd>
                </div>
                <div>
                    <dt>Successful payments</dt>
                    <dd>{{ $successfulPayments }}</dd>
                </div>
            </dl>
        </article>

        <div class="profile-detail-stack">
            <article class="surface-card">
                <div class="card-head">
                    <div>
                        <p class="card-kicker">Contact record</p>
                        <h2>Contact Information</h2>
                    </div>
                </div>

                <div class="detail-list">
                    <div class="detail-row">
                        <span class="detail-icon">@include('member.partials.icon', ['name' => 'mail'])</span>
                        <div>
                            <span class="detail-label">Email</span>
                            <strong>{{ $user->email }}</strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <span class="detail-icon">@include('member.partials.icon', ['name' => 'phone'])</span>
                        <div>
                            <span class="detail-label">Phone number</span>
                            <strong>{{ $user->phone ?: ($member->phone ?: 'No phone on file') }}</strong>
                        </div>
                    </div>
                </div>
            </article>

            <article class="surface-card">
                <div class="card-head">
                    <div>
                        <p class="card-kicker">Membership status</p>
                        <h2>Account Details</h2>
                    </div>
                </div>

                <dl class="info-grid">
                    <div>
                        <dt>Plan type</dt>
                        <dd>{{ $member->membershipPlan?->name ?? $member->membership_type ?? 'Standard' }}</dd>
                    </div>
                    <div>
                        <dt>Expiry date</dt>
                        <dd>{{ optional($member->expiry_date)?->format('M d, Y') ?? 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Last visit</dt>
                        <dd>{{ optional($user->last_visit_at)?->format('M d, Y - h:i A') ?? 'No recent visit' }}</dd>
                    </div>
                    <div>
                        <dt>Latest attended class</dt>
                        <dd>{{ $latestAttendance?->class_name ?? 'No class attendance yet' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="surface-card">
                <div class="card-head">
                    <div>
                        <p class="card-kicker">Progress tracking</p>
                        <h2>Fitness Progress</h2>
                    </div>
                </div>

                <div class="detail-list">
                    <div class="detail-row">
                        <span class="detail-icon">@include('member.partials.icon', ['name' => 'chart'])</span>
                        <div>
                            <span class="detail-label">Active bookings</span>
                            <strong>{{ $bookingsCount }}</strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <span class="detail-icon">@include('member.partials.icon', ['name' => 'check'])</span>
                        <div>
                            <span class="detail-label">Payments completed</span>
                            <strong>{{ $successfulPayments }}</strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <span class="detail-icon">@include('member.partials.icon', ['name' => 'trophy'])</span>
                        <div>
                            <span class="detail-label">Progress note</span>
                            <strong>{{ $member->membership_type === 'VIP' ? 'Unlimited class tracking enabled' : ($member->membership_type === 'Premium' ? 'Premium tracking for up to 10 bookings' : 'Basic members may book 1 class at a time') }}</strong>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection
