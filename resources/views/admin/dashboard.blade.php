@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Admin Dashboard</h1>
            <p class="page-description">Live operational metrics from your gym database, with quick access to the areas that need attention most.</p>
        </div>
    </div>

    <div class="summary-grid">
        <a href="{{ route('admin.users') }}" class="summary-card card-panel-link">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'members'])</div>
            <p class="summary-title">Total users</p>
            <p class="summary-value">{{ number_format($stats['total_users']) }}</p>
            <small>{{ $stats['user_change'] }} versus last month</small>
        </a>
        <a href="{{ route('admin.attendance') }}" class="summary-card card-panel-link">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'active'])</div>
            <p class="summary-title">Classes today</p>
            <p class="summary-value">{{ number_format($stats['active_today']) }}</p>
            <small>{{ $stats['active_today_meta'] }}</small>
        </a>
        <a href="{{ route('admin.reports') }}" class="summary-card card-panel-link">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'revenue'])</div>
            <p class="summary-title">Revenue this month</p>
            <p class="summary-value">PHP {{ number_format($stats['revenue'], 2) }}</p>
            <small>{{ $stats['revenue_change'] }} versus last month</small>
        </a>
    </div>

    <div class="dashboard-split">
        <section class="section-card">
            <div class="section-heading">
                <span class="inline-icon">@include('admin.partials.icon', ['name' => 'dashboard'])</span>
                <strong>Quick Actions</strong>
            </div>
            <div class="quick-action-grid">
                @foreach ($quickStats as $item)
                    <a href="{{ $item['route'] }}" class="quick-action-card card-panel-link">
                        <span class="quick-action-label">{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                        <p>{{ $item['meta'] }}</p>
                        <span class="text-link quick-action-link nowrap-link">{{ $item['button'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="section-card">
            <div class="section-heading">
                <span class="inline-icon">@include('admin.partials.icon', ['name' => 'payments'])</span>
                <strong>Latest Transactions</strong>
            </div>
            <div class="dashboard-list">
                @forelse ($recentPayments as $payment)
                    <div class="dashboard-list-item">
                        <div>
                            <strong>{{ $payment->member_name ?? 'Unknown member' }}</strong>
                            <p>{{ $payment->payment_method }} | {{ $payment->payment_status }}</p>
                        </div>
                        <div class="dashboard-list-meta">
                            <strong>PHP {{ number_format((float) $payment->amount, 2) }}</strong>
                            <span>{{ optional($payment->payment_date)?->format('M d, Y h:i A') ?? 'No date' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="dashboard-list-item empty-state-inline">No payment activity yet.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
