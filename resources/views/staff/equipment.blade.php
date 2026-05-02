@extends('layouts.staff', ['title' => 'Equipment'])

@section('content')
    <section class="hero-panel split">
        <div>
            <span class="eyebrow">Floor readiness</span>
            <h1 class="page-title">Equipment Status</h1>
            <p class="page-subtitle">Monitor machine condition, spot maintenance risks early, and help keep training areas safe and usable.</p>
        </div>
        <div class="hero-pill">
            <span>Needs attention</span>
            <strong>{{ $issues }}</strong>
        </div>
    </section>

    <div class="alert {{ $issues > 0 ? 'danger' : 'success' }}">
        {{ $issues > 0 ? $issues.' equipment item(s) need follow-up or maintenance review.' : 'All tracked equipment is currently in good standing.' }}
    </div>

    <div class="search-row compact">
        <div class="search-field staff-search-field">
            <span class="field-icon">@include('staff.partials.icon', ['name' => 'search'])</span>
            <input
                class="staff-input"
                id="equipmentSearch"
                type="text"
                value="{{ $search }}"
                list="equipmentSuggestions"
                placeholder="Search by equipment name, condition, or description">
        </div>
        <datalist id="equipmentSuggestions">
            @foreach ($equipment as $item)
                <option value="{{ $item->name }}"></option>
                <option value="{{ $item->status }}"></option>
            @endforeach
        </datalist>
    </div>

    <section class="panel-card">
        <div class="table-wrap">
            <table class="staff-table" id="equipmentTable">
                <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Quantity</th>
                    <th>Usage Status</th>
                    <th>Latest Maintenance</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($equipment as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->name }}</strong>
                            <div class="table-subtle">{{ $item->description ?: 'No description provided.' }}</div>
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td><span class="badge {{ strtolower(str_replace(' ', '-', $item->status)) }}">{{ $item->status }}</span></td>
                        <td>{{ optional($item->last_maintenance_date)->format('Y-m-d') ?: 'Not set' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-cell">No equipment matched your search.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/staff-directory.js') }}"></script>
@endsection
