@extends('layouts.admin', ['title' => 'Equipment Monitoring'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Equipment Monitoring</h1>
            <p class="page-description">Keep an eye on machine readiness, maintenance backlog, and the condition of the training floor.</p>
        </div>
    </div>

    <div class="summary-grid summary-grid-4">
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'equipment'])</div>
            <p class="summary-title">Tracked Items</p>
            <p class="summary-value">{{ $stats['tracked_items'] }}</p>
            <small>{{ $stats['total_units'] }} total units recorded</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'active'])</div>
            <p class="summary-title">Available</p>
            <p class="summary-value">{{ $stats['available_items'] }}</p>
            <small>Ready for member use</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'warning'])</div>
            <p class="summary-title">Needs Attention</p>
            <p class="summary-value">{{ $stats['attention_items'] }}</p>
            <small>Maintenance, damaged, or under-repair items</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'reports'])</div>
            <p class="summary-title">Used In Classes</p>
            <p class="summary-value">{{ $stats['linked_to_classes'] }}</p>
            <small>Equipment assigned to scheduled classes</small>
        </div>
    </div>

    <section class="section-card">
        <div class="section-heading">
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'equipment'])</span>
            <strong>Monitoring Only</strong>
        </div>
        <p style="margin: 0; color: var(--muted-text, #5f6b7a);">
            Equipment entries are now created from the staff equipment page. This admin view is for monitoring all recorded equipment, including items added by staff.
        </p>
    </section>

    <section class="section-card">
        <div class="section-heading">
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'equipment'])</span>
            <strong>Equipment Inventory</strong>
        </div>
        <div class="table-wrap">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Quantity</th>
                        <th>Usage Status</th>
                        <th>Latest Maintenance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipmentItems as $equipment)
                        <tr>
                            <td>
                                <strong>{{ $equipment->name }}</strong>
                                <div class="table-subtle">{{ $equipment->description ?: 'No description provided.' }}</div>
                            </td>
                            <td>{{ $equipment->quantity }}</td>
                            <td><span class="pill {{ str($equipment->status)->slug('-') }}">{{ $equipment->status }}</span></td>
                            <td>{{ optional($equipment->last_maintenance_date)->format('M d, Y') ?: 'Not set' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No equipment has been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($equipmentItems, 'hasPages') && $equipmentItems->hasPages())
            <div class="pagination">
                {{ $equipmentItems->links() }}
            </div>
        @endif
    </section>
@endsection
