@extends('layouts.admin', ['title' => 'Payments'])

@section('content')
    <div class="page-header">
        <h1 class="page-title">Revenue management</h1>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'revenue'])</div>
            <p class="summary-title">Total Revenue</p>
            <p class="summary-value" id="statTotalRevenue">PHP 0.00</p>
            <small>Paid transactions only</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'pending'])</div>
            <p class="summary-title">Pending payments</p>
            <p class="summary-value" id="statPendingCount">0</p>
            <small id="statPendingTotal">PHP 0.00 in total</small>
        </div>
        <div class="summary-card">
            <div class="card-icon">@include('admin.partials.icon', ['name' => 'warning'])</div>
            <p class="summary-title">Failed transaction</p>
            <p class="summary-value" id="statFailedCount">0</p>
            <small>failed payments</small>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-field">
            <span class="field-icon">@include('admin.partials.icon', ['name' => 'search'])</span>
            <input class="input" id="paymentSearch" type="text" placeholder="Search by member name, member ID, payment ID, email, or method...">
        </div>
        <select class="select" id="paymentStatusFilter">
            <option value="All">All status</option>
            <option value="Paid">Paid</option>
            <option value="Pending">Pending</option>
            <option value="Failed">Failed</option>
        </select>
        <select class="select" id="paymentMethodFilter">
            <option value="All">All methods</option>
        </select>
    </div>

    <div class="table-wrap">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Transaction ID</th>
                    <th>Member</th>
                    <th>Requested Plan</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="paymentsTbody">
                <tr>
                    <td colspan="10">Loading payments...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span id="paymentsPageInfo">Showing 0 to 0 of 0 payments</span>
        <div>
            <button class="btn" id="paymentsPrevBtn">Previous</button>
            <button class="btn" id="paymentsNextBtn">Next</button>
        </div>
    </div>

    <div class="toast" id="paymentsToast"></div>

    <div class="modal-backdrop" id="paymentDetailsModal" hidden>
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Payment details</h2>
                    <p class="confirm-copy">Review the payment request and proof information before approving or rejecting.</p>
                </div>
                <button type="button" class="modal-close-btn" id="paymentDetailsClose" aria-label="Close details modal">×</button>
            </div>
            <div class="detail-layout" id="paymentDetailsContent">
                <section class="detail-card">
                    <h3>Payment summary</h3>
                    <div class="detail-row">
                        <span>Payment ID</span>
                        <strong id="detailPaymentId"></strong>
                    </div>
                    <div class="detail-row">
                        <span>Transaction ID</span>
                        <strong id="detailTransactionId"></strong>
                    </div>
                    <div class="detail-row">
                        <span>Requested plan</span>
                        <strong id="detailPlan"></strong>
                    </div>
                    <div class="detail-row">
                        <span>Amount</span>
                        <strong id="detailAmount"></strong>
                    </div>
                </section>

                <section class="detail-card">
                    <h3>Member details</h3>
                    <div class="detail-row">
                        <span>Member</span>
                        <strong id="detailMember"></strong>
                    </div>
                    <div class="detail-row">
                        <span>Payment method</span>
                        <strong id="detailMethod"></strong>
                    </div>
                    <div class="detail-row">
                        <span>Status</span>
                        <strong id="detailStatus"></strong>
                    </div>
                    <div class="detail-row">
                        <span>Date</span>
                        <strong id="detailDate"></strong>
                    </div>
                </section>

                <section class="detail-card" id="detailVerificationCard">
                    <h3>Verification info</h3>
                    <div class="detail-row">
                        <span>Reference number</span>
                        <strong id="detailReference"></strong>
                    </div>
                    <div class="detail-row" id="detailGcashWrapper" style="display:none;">
                        <span>GCash number</span>
                        <strong id="detailGcash"></strong>
                    </div>
                    <div class="detail-row" id="detailProofWrapper" style="display:none;">
                        <span>Proof image</span>
                        <strong id="detailProof"></strong>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/payments.js') }}"></script>
@endsection
