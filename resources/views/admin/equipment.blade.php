@extends('layouts.admin', ['title' => 'Equipment Management'])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Equipment Management</h1>
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
            <span class="inline-icon">@include('admin.partials.icon', ['name' => 'plus'])</span>
            <strong>Add Equipment Item</strong>
        </div>

        @if (session('success'))
            <div class="field-error" style="color: #114d05; background: rgba(220, 248, 198, 0.45); border-radius: 16px; padding: 14px; margin-bottom: 18px; border: 1px solid rgba(52, 211, 153, 0.3);">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.equipment.store') }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 16px;">
            @csrf

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="{{ $errors->has('name') ? 'input-invalid' : '' }}">
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" class="{{ $errors->has('quantity') ? 'input-invalid' : '' }}">
                @error('quantity')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" class="{{ $errors->has('status') ? 'input-invalid' : '' }}">
                    <option value="Available" {{ old('status') === 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="In Use" {{ old('status') === 'In Use' ? 'selected' : '' }}>In Use</option>
                    <option value="Maintenance" {{ old('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
                @error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field" style="grid-column: span 3;">
                <label for="description">Description</label>
                <input id="description" name="description" type="text" value="{{ old('description') }}" class="{{ $errors->has('description') ? 'input-invalid' : '' }}">
                @error('description')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div style="grid-column: span 3; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn primary">Add equipment</button>
            </div>
        </form>
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
    </section>
@endsection
