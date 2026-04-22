@extends('layouts.admin', ['title' => 'User Management'])

@section('toolbar-actions')
    <button class="btn" id="addUserBtn"><span class="button-icon">@include('admin.partials.icon', ['name' => 'plus'])</span><span>Add new user</span></button>
@endsection

@section('content')
    <div class="page-header">
        <h1 class="page-title">User Management</h1>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'active'])</div>
            <p class="summary-title">Active users</p>
            <p class="summary-value" id="statActive">0</p>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'inactive'])</div>
            <p class="summary-title">Inactive users</p>
            <p class="summary-value" id="statInactive">0</p>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'suspended'])</div>
            <p class="summary-title">Suspended users</p>
            <p class="summary-value" id="statSuspended">0</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-field">
            <span class="field-icon">@include('admin.partials.icon', ['name' => 'search'])</span>
            <input class="input" id="searchInput" type="text" list="userSuggestions" placeholder="Search members by name, ID, or email...">
        </div>
        <datalist id="userSuggestions"></datalist>
        <select class="select" id="roleFilter">
            <option value="All">All roles</option>
            <option value="Admin">Admin</option>
            <option value="Staff">Staff</option>
            <option value="Member">Member</option>
        </select>
        <select class="select" id="statusFilter">
            <option value="All">All status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Suspended">Suspended</option>
        </select>
    </div>

    <div class="table-wrap">
        <table class="user-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Contact Information</th>
                    <th>Role</th>
                    <th>Membership Type</th>
                    <th>Status</th>
                    <th>Join Date</th>
                    <th>Expiry Date</th>
                    <th>Last Visit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTbody">
                <tr>
                    <td colspan="10">Loading users...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span id="pageInfo">Showing 0 to 0 of 0 users</span>
        <div>
            <button class="btn" id="prevPageBtn">Previous</button>
            <button class="btn" id="nextPageBtn">Next</button>
        </div>
    </div>

    <div class="modal-backdrop" id="userModal">
        <div class="modal-card">
            <h2 class="modal-title" id="modalTitle">Add New User</h2>
            <form id="userForm" class="form-grid">
                <input type="hidden" id="userId">
                <div class="field">
                    <label for="fullName">Full Name</label>
                    <input id="fullName" name="full_name" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone">
                </div>
                <div class="field" id="passwordField">
                    <label for="password">Password</label>
                    <div class="password-control">
                        <input id="password" name="password" type="password" placeholder="Create account password">
                        <button type="button" class="password-toggle-btn" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                </div>
                <div class="field" id="passwordConfirmationField">
                    <label for="passwordConfirmation">Confirm Password</label>
                    <div class="password-control">
                        <input id="passwordConfirmation" name="password_confirmation" type="password" placeholder="Confirm account password">
                        <button type="button" class="password-toggle-btn" data-password-toggle="passwordConfirmation" aria-label="Show password">Show</button>
                    </div>
                    <div class="field-error" id="passwordMismatchError" style="display: none;"></div>
                </div>
                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="Staff">Staff</option>
                        <option value="Member">Member</option>
                    </select>
                </div>
                <div class="field" id="membershipTypeField">
                    <label for="membershipType">Membership Type</label>
                    <select id="membershipType" name="membership_type">
                        <option value="">Select membership</option>
                        <option value="Basic">Basic</option>
                        <option value="VIP">VIP</option>
                        <option value="Premium">Premium</option>
                    </select>
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                <div class="field">
                    <label for="joinDate">Join Date</label>
                    <input id="joinDate" name="join_date" type="date">
                </div>
                <div class="field">
                    <label for="expiryDate">Expiry Date</label>
                    <input id="expiryDate" name="expiry_date" type="date">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn primary" id="saveBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>
@endsection

@section('scripts')
    <script src="{{ asset('js/user-management.js') }}"></script>
@endsection
