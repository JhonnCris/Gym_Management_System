<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Staff' }} - WeDumbell</title>
    <link rel="stylesheet" href="{{ asset('css/staff.css') }}">
</head>
<body class="staff-body">
<div class="staff-shell">
    <aside class="staff-sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand-row">
                @include('partials.sidebar-brand', ['href' => route('staff.dashboard'), 'subtitle' => 'Staff Operations'])
                <button id="staffSidebarToggle" type="button" class="sidebar-toggle-btn" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar">
                    <span class="sidebar-toggle-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                    </span>
                </button>
            </div>

            <nav class="sidebar-nav" aria-label="Staff navigation">
                <a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}">
                    <span class="nav-icon">@include('staff.partials.icon', ['name' => 'dashboard'])</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link {{ request()->routeIs('staff.checkin*') ? 'active' : '' }}" href="{{ route('staff.checkin') }}">
                    <span class="nav-icon">@include('staff.partials.icon', ['name' => 'checkin'])</span>
                    <span>Check-in</span>
                </a>
                <a class="nav-link {{ request()->routeIs('staff.classes') ? 'active' : '' }}" href="{{ route('staff.classes') }}">
                    <span class="nav-icon">@include('staff.partials.icon', ['name' => 'classes'])</span>
                    <span>Classes</span>
                </a>
                <a class="nav-link {{ request()->routeIs('staff.members') ? 'active' : '' }}" href="{{ route('staff.members') }}">
                    <span class="nav-icon">@include('staff.partials.icon', ['name' => 'members'])</span>
                    <span>Member List</span>
                </a>
                <a class="nav-link {{ request()->routeIs('staff.equipment') ? 'active' : '' }}" href="{{ route('staff.equipment') }}">
                    <span class="nav-icon">@include('staff.partials.icon', ['name' => 'equipment'])</span>
                    <span>Equipment</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="staff-meta">
                <span class="meta-label">Signed in as</span>
                <strong>{{ auth()->user()->full_name }}</strong>
            </div>

            <form method="POST" action="{{ url('/logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-link">
                    <span class="nav-icon">@include('staff.partials.icon', ['name' => 'logout'])</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="staff-content">
        @yield('content')
    </main>
</div>
@yield('scripts')
<script>
    (function () {
        const STORAGE_KEY = 'wedumbell_staff_sidebar_collapsed';
        const toggleButton = document.getElementById('staffSidebarToggle');
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
