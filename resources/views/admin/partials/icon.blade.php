@switch($name)
    @case('dashboard')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="3" y="14" width="5" height="7" rx="1.5"/><rect x="10" y="12" width="11" height="9" rx="1.5"/></svg>
        @break
    @case('users')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V21"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M21 21v-1.5a4 4 0 0 0-3-3.87"/><path d="M15 4.13a3.5 3.5 0 0 1 0 6.74"/></svg>
        @break
    @case('payments')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/><path d="M7 15h3"/><path d="M13 15h4"/></svg>
        @break
    @case('reports')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5h16"/><path d="M7 16V9"/><path d="M12 16V5"/><path d="M17 16v-7"/></svg>
        @break
    @case('classes')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
        @break
    @case('attendance')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6"/><path d="M9 6.5a3 3 0 1 1 6 0c0 3-3 4-3 6"/><circle cx="12" cy="20" r="1"/></svg>
        @break
    @case('equipment')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3 17.7 3.3a2.12 2.12 0 1 1 3 3l-3 3"/><path d="m11 10 3 3"/><path d="m2 22 6-6"/><path d="m7 17 7.5-7.5a2.12 2.12 0 0 0 0-3l-1-1a2.12 2.12 0 0 0-3 0L3 13"/></svg>
        @break
    @case('logout')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        @break
    @case('search')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.35-4.35"/></svg>
        @break
    @case('plus')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
        @break
    @case('export')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/></svg>
        @break
    @case('members')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
        @break
    @case('active')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.4 2.4L15.8 9.5"/></svg>
        @break
    @case('inactive')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 12h6"/></svg>
        @break
    @case('suspended')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7"/><path d="m15.5 8.5-7 7"/></svg>
        @break
    @case('revenue')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><path d="M16 7.5A3.5 3.5 0 0 0 12.5 5h-1A3.5 3.5 0 0 0 8 8.5c0 1.93 1.57 3.5 3.5 3.5h1A3.5 3.5 0 0 1 16 15.5 3.5 3.5 0 0 1 12.5 19h-1A3.5 3.5 0 0 1 8 16.5"/></svg>
        @break
    @case('visits')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
        @break
    @case('pending')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        @break
    @case('warning')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        @break
    @case('notifications')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.17V11a6 6 0 1 0-12 0v3.17a2 2 0 0 1-.6 1.43L4 17h5"/><path d="M10 17a2 2 0 0 0 4 0"/></svg>
        @break
@endswitch
