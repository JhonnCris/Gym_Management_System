<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - WeDumbell</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand-row">
                @include('partials.sidebar-brand', ['href' => route('admin.dashboard'), 'subtitle' => 'Admin Portal'])
                <button id="adminSidebarToggle" type="button" class="sidebar-toggle-btn" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar">
                    <span class="sidebar-toggle-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                    </span>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'dashboard'])</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'users'])</span>
                    <span>User Management</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.payments') ? 'active' : '' }}" href="{{ route('admin.payments') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'payments'])</span>
                    <span>Payment Management</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.classes') ? 'active' : '' }}" href="{{ route('admin.classes') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'classes'])</span>
                    <span>Class Management</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.attendance') ? 'active' : '' }}" href="{{ route('admin.attendance') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'attendance'])</span>
                    <span>Attendance Management</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.equipment') ? 'active' : '' }}" href="{{ route('admin.equipment') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'equipment'])</span>
                    <span>Equipment Management</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'reports'])</span>
                    <span>Reports</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <form method="POST" action="{{ url('/logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-link">
                    <span class="nav-icon">@include('admin.partials.icon', ['name' => 'logout'])</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="admin-content">
        <div class="admin-toolbar">
            <div class="toolbar-actions">
                @yield('toolbar-actions')
            </div>
            <button type="button" class="notification-trigger" id="adminNotificationToggle" aria-expanded="false" aria-controls="adminNotificationPanel">
                <span class="button-icon">@include('admin.partials.icon', ['name' => 'notifications'])</span>
                <span>Notifications</span>
                <span class="notification-count" id="adminNotificationCount">0</span>
            </button>
        </div>
        @yield('content')
    </main>
</div>
<div class="notification-panel" id="adminNotificationPanel" hidden>
    <div class="notification-panel-head">
        <div>
            <strong>Admin Notifications</strong>
            <p>Recent account and system actions</p>
        </div>
        <button type="button" class="panel-clear-btn" id="adminNotificationClear">Clear all</button>
    </div>
    <div class="notification-list" id="adminNotificationList">
        <div class="notification-empty">No notifications yet.</div>
    </div>
</div>
<div class="modal-backdrop confirm-backdrop" id="adminConfirmModal">
    <div class="modal-card confirm-card">
        <h2 class="modal-title" id="adminConfirmTitle">Please Confirm</h2>
        <p class="confirm-copy" id="adminConfirmMessage">Are you sure you want to continue?</p>
        <div class="modal-actions">
            <button type="button" class="btn" id="adminConfirmCancel">Cancel</button>
            <button type="button" class="btn primary" id="adminConfirmAccept">Confirm</button>
        </div>
    </div>
</div>
<script src="{{ asset('js/admin-notifications.js') }}"></script>
@yield('scripts')
<script>
    (function () {
        const STORAGE_KEY = 'wedumbell_admin_sidebar_collapsed';
        const toggleButton = document.getElementById('adminSidebarToggle');
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
