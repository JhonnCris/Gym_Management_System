@extends('layouts.staff', ['title' => 'Members'])

@section('content')
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Member directory</span>
            <h1 class="page-title">Member List</h1>
            <p class="page-subtitle">Look up contact details, membership status, and the latest activity from one front-desk view.</p>
        </div>
    </section>

    <div class="search-row compact">
        <div class="search-field staff-search-field">
            <span class="field-icon">@include('staff.partials.icon', ['name' => 'search'])</span>
            <input
                class="staff-input"
                id="membersSearch"
                type="text"
                value="{{ $search }}"
                list="memberSuggestions"
                placeholder="Search by name, email, phone, membership, or member ID">
        </div>
        <datalist id="memberSuggestions">
            @foreach ($members as $member)
                <option value="{{ $member->full_name }}"></option>
                <option value="{{ $member->email }}"></option>
                <option value="M{{ str_pad((string) $member->member_id, 3, '0', STR_PAD_LEFT) }}"></option>
                <option value="{{ $member->membership_type }}"></option>
            @endforeach
        </datalist>
    </div>

    <section class="panel-card">
        <div class="table-wrap">
            <table class="staff-table" id="membersTable">
                <thead>
                <tr>
                    <th>Member ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Membership</th>
                    <th>Status</th>
                    <th>Last Visit</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>M{{ str_pad((string) $member->member_id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <div class="table-person">
                                <div class="avatar">{{ strtoupper(substr($member->full_name ?? 'M', 0, 1)) }}</div>
                                <div>
                                    <strong>{{ $member->full_name }}</strong>
                                    <span>{{ $member->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ $member->phone ?: 'No phone on file' }}</td>
                        <td>{{ $member->membership_type }}</td>
                        <td><span class="badge {{ strtolower($member->member_status ?? 'active') }}">{{ $member->member_status ?? 'Active' }}</span></td>
                        <td>{{ optional($member->last_visit_at)->format('Y-m-d h:i A') ?: 'No visit yet' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-cell">No members matched your search.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div class="pagination">
                {{ $members->links() }}
            </div>
        @endif
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/staff-directory.js') }}"></script>
@endsection
