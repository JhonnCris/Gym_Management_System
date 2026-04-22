<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Member' }} - WeDumbell</title>
    <link rel="stylesheet" href="{{ asset('css/member.css') }}">
</head>
<body class="member-body">
<div class="member-shell">
    <aside class="member-sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand-row">
                @include('partials.sidebar-brand', ['href' => route('member.dashboard'), 'subtitle' => 'Member Portal'])
                <button id="memberSidebarToggle" type="button" class="sidebar-toggle-btn" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar">
                    <span class="sidebar-toggle-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                    </span>
                </button>
            </div>

            <nav class="sidebar-nav" aria-label="Member navigation">
                <a class="nav-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" href="{{ route('member.dashboard') }}">
                    <span class="nav-icon">@include('member.partials.icon', ['name' => 'dashboard'])</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link {{ request()->routeIs('member.profile') ? 'active' : '' }}" href="{{ route('member.profile') }}">
                    <span class="nav-icon">@include('member.partials.icon', ['name' => 'profile'])</span>
                    <span>My Profile</span>
                </a>
                <a class="nav-link {{ request()->routeIs('member.classes') ? 'active' : '' }}" href="{{ route('member.classes') }}">
                    <span class="nav-icon">@include('member.partials.icon', ['name' => 'classes'])</span>
                    <span>Classes</span>
                </a>
                <a class="nav-link {{ request()->routeIs('member.payments') ? 'active' : '' }}" href="{{ route('member.payments') }}">
                    <span class="nav-icon">@include('member.partials.icon', ['name' => 'payments'])</span>
                    <span>Payments</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="member-meta">
                <span class="meta-label">Signed in as</span>
                <strong>{{ $currentUser->full_name }}</strong>
                <span>{{ $currentMember->membership_type ?? 'Member' }} plan</span>
            </div>

            <form method="POST" action="{{ url('/logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-link">
                    <span class="nav-icon">@include('member.partials.icon', ['name' => 'logout'])</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="member-content">
        <header class="member-topbar">
            <div>
                <p class="eyebrow">Member portal</p>
                <h1 class="page-title">@yield('page-title')</h1>
                <p class="page-subtitle">@yield('page-subtitle')</p>
            </div>
            <div class="topbar-card">
                <span class="topbar-label">Membership status</span>
                <span class="status-badge {{ strtolower($currentMember->status ?? 'active') }}">{{ $currentMember->status ?? 'Active' }}</span>
                <span class="topbar-meta">Renews {{ optional($currentMember->expiry_date)?->format('M d, Y') ?? 'TBD' }}</span>
            </div>
        </header>

        @yield('content')
    </main>
</div>
@yield('scripts')
<script>
    (function () {
        const STORAGE_KEY = 'wedumbell_member_sidebar_collapsed';
        const toggleButton = document.getElementById('memberSidebarToggle');
        const root = document.body;

        if (!toggleButton) {
            return;
        }

        const setCollapsed = (collapsed) => {
            root.classList.toggle('sidebar-collapsed', collapsed);
            toggleButton.setAttribute('aria-expanded', String(!collapsed));
            toggleButton.setAttribute('aria-label', collapsed ? 'Open sidebar' : 'Collapse sidebar');
            toggleButton.title = collapsed ? 'Open sidebar' : 'Collapse sidebar';
        };

        const savedState = localStorage.getItem(STORAGE_KEY);
        setCollapsed(savedState === 'true');

        toggleButton.addEventListener('click', () => {
            const collapsed = !root.classList.contains('sidebar-collapsed');
            setCollapsed(collapsed);
            localStorage.setItem(STORAGE_KEY, String(collapsed));
        });
    })();
</script>
</body>
</html>
