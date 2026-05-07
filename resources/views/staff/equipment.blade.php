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

    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

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

<form method="POST" action="{{ route('staff.equipment.store') }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 16px;">
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
                    <option value="Under Repair" {{ old('status') === 'Under Repair' ? 'selected' : '' }}>Under Repair</option>
                    <option value="Maintenance" {{ old('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
                @error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field" style="grid-column: span 3;">
                <label for="description">Description</label>
                <input id="description" name="description" type="text" value="{{ old('description') }}" class="{{ $errors->has('description') ? 'input-invalid' : '' }}">
                @error('description')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <style>
                .field-error {
                    color: #dc3545;
                    font-size: 0.875rem;
                    margin-top: 0.25rem;
                }
                .input-invalid {
                    border-color: #dc3545;
                }
                .field label {
                    display: block;
                    margin-bottom: 0.5rem;
                    font-weight: 600;
                }
                .field{
                    display: flex;
                    flex-direction: column;
                }
                .form-grid {
                    display: grid;
                }
                .form-grid .field {
                    margin-bottom: 1rem;
                }
                .form-grid button {
                    padding: 0.5rem 1rem;
                    font-size: 1rem;
                }
                .form-grid .btn.primary {
                    background-color: #080808;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
                .form-grid .btn.primary:hover {
                background-color: #333;
                }
                .form-grid .btn.primary:active {
                    background-color: #555;
                }
                .form-grid .btn.primary:disabled {
                    background-color: #ccc;
                    cursor: not-allowed;
                }
                .form-grid .btn.primary:disabled:hover {
                    background-color: #ccc;
                }
                .form-grid .btn.primary:disabled:active {
                    background-color: #ccc;
                }
                .form-grid-column {
                    grid-column: span 3;
                    display: flex;
                    justify-content: flex-end;
                    height: 50px;
                }
                .form-grid-column .btn {
                    height: 100%;
                }
            </style>

            <div class="form-grid-column" style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn primary">Add equipment</button>
            </div>
        </form>
    </section>
    
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
                        <td colspan="4" class="empty-cell">No equipment matched your search.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($equipment->hasPages())
            <div class="pagination">
                {{ $equipment->links() }}
            </div>
        @endif
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/staff-directory.js') }}"></script>
@endsection
