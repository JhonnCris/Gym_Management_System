@switch($name)
    @case('dashboard')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="3" y="14" width="5" height="7" rx="1.5"/><rect x="10" y="12" width="11" height="9" rx="1.5"/></svg>
        @break
    @case('checkin')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
        @break
    @case('classes')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
        @break
    @case('members')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
        @break
    @case('equipment')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3 17.7 3.3a2.12 2.12 0 1 1 3 3l-3 3"/><path d="m11 10 3 3"/><path d="m2 22 6-6"/><path d="m7 17 7.5-7.5a2.12 2.12 0 0 0 0-3l-1-1a2.12 2.12 0 0 0-3 0L3 13"/></svg>
        @break
    @case('logout')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        @break
    @case('calendar')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/></svg>
        @break
    @case('clock')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        @break
    @case('search')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.35-4.35"/></svg>
        @break
    @case('status')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.4 2.4L15.8 9.5"/></svg>
        @break
    @case('warning')
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        @break
@endswitch
