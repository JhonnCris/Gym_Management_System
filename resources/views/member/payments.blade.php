@extends('layouts.member', ['title' => 'Payments', 'currentUser' => $user, 'currentMember' => $member])

@section('page-title', 'Payment History')
@section('page-subtitle', 'Billing information and payment records presented in a cleaner, more standard account summary.')

@section('content')
    <section class="stat-grid compact-stats">
        <article class="stat-card accent-blue">
            <div class="stat-icon">@include('member.partials.icon', ['name' => 'payments'])</div>
            <span class="stat-label">Paid invoices</span>
            <strong class="stat-value">{{ $paymentSummary['paid_count'] }}</strong>
            <p class="stat-meta">Successful payment records in your account</p>
        </article>
        <article class="stat-card accent-green">
            <div class="stat-icon green">@include('member.partials.icon', ['name' => 'membership'])</div>
            <span class="stat-label">Total paid</span>
            <strong class="stat-value">PHP {{ number_format($paymentSummary['paid_total'], 2) }}</strong>
            <p class="stat-meta">Sum of paid membership transactions</p>
        </article>
        <article class="stat-card accent-amber">
            <div class="stat-icon amber">@include('member.partials.icon', ['name' => 'calendar'])</div>
            <span class="stat-label">Last payment date</span>
            <strong class="stat-value stat-value-sm">{{ $paymentSummary['last_payment_date'] }}</strong>
            <p class="stat-meta">Most recent successful or latest available payment</p>
        </article>
    </section>

    <section class="surface-card">
        <div class="card-head">
            <div>
                <p class="card-kicker">Subscription update</p>
                <h2>Choose a Plan and Pay</h2>
            </div>
        </div>

        <div class="plan-grid" id="memberPlanGrid">
            @foreach ($plans as $plan)
                <article
                    class="plan-card {{ $member->membership_type === $plan['name'] ? 'current' : '' }}"
                    data-plan-card
                    data-plan-name="{{ $plan['name'] }}"
                    data-plan-price="{{ number_format($plan['price'], 2, '.', '') }}">
                    <div class="plan-card-head">
                        <div>
                            <h3>{{ $plan['name'] }}</h3>
                            <p>{{ $plan['description'] }}</p>
                        </div>
                        @if ($member->membership_type === $plan['name'])
                            <span class="status-badge active">Current plan</span>
                        @endif
                    </div>
                    <strong class="plan-price">PHP {{ number_format($plan['price'], 2) }}</strong>
                    <span class="plan-duration">Valid for {{ $plan['duration_months'] }} month</span>
                    <div class="plan-features">
                        @foreach ($plan['features'] as $feature)
                            <span>{{ $feature }}</span>
                        @endforeach
                    </div>
                    <button type="button" class="btn {{ $member->membership_type === $plan['name'] ? 'subtle' : 'primary' }}" data-select-plan>
                        {{ $member->membership_type === $plan['name'] ? 'Current Plan' : 'Select Plan' }}
                    </button>
                </article>
            @endforeach
        </div>

        <form class="subscription-form" id="memberSubscriptionForm" enctype="multipart/form-data">
            <input type="hidden" name="membership_type" id="memberPlanInput" value="{{ $member->membership_type }}">
            <div class="form-field">
                <label for="payment_method">Payment method</label>
                <select id="payment_method" name="payment_method" class="member-input">
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div class="subscription-summary">
                <span class="stat-label">Selected plan</span>
                <strong id="selectedPlanLabel">{{ $member->membership_type }}</strong>
                @php
                    $currentPlan = collect($plans)->firstWhere('name', $member->membership_type) ?? $plans[0];
                @endphp
                <span class="stat-meta" id="selectedPlanPrice">PHP {{ number_format($currentPlan['price'], 2) }}</span>
            </div>
            <button type="button" class="btn primary" id="memberSubscribeBtn">Continue to Payment Details</button>
        </form>
    </section>

    <section class="surface-card">
        <div class="card-head">
            <div>
                <p class="card-kicker">Current membership</p>
                <h2>Billing Overview</h2>
            </div>
        </div>

        <div class="billing-overview">
            <div class="billing-stat">
                <span class="stat-label">Plan</span>
                <strong>{{ $member->membership_type ?? 'Standard' }}</strong>
                <span class="stat-meta">Active membership</span>
            </div>
            <div class="billing-stat">
                <span class="stat-label">Renewal date</span>
                <strong>{{ optional($member->expiry_date)?->format('M d, Y') ?? 'Not set' }}</strong>
                <span class="stat-meta">Based on member expiry record</span>
            </div>
            <div class="billing-stat">
                <span class="stat-label">Latest paid amount</span>
                <strong>{{ $latestPaid ? 'PHP '.number_format((float) $latestPaid->amount, 2) : 'No payment yet' }}</strong>
                <span class="stat-meta">{{ $latestPaid?->payment_method ?? 'Payment method unavailable' }}</span>
            </div>
            <div class="billing-stat">
                <span class="stat-label">Approval flow</span>
                <strong>Admin review required</strong>
                <span class="stat-meta">Submitted payment requests stay pending until an admin verifies and approves them.</span>
            </div>
        </div>
    </section>

    <section class="surface-card">
        <div class="card-head">
            <div>
                <p class="card-kicker">Transaction ledger</p>
                <h2>Payment History</h2>
            </div>
        </div>

        <div class="table-wrap">
            <table class="member-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ optional($payment->payment_date)?->format('Y-m-d h:i A') ?? 'No date' }}</td>
                            <td>PAY-{{ str_pad((string) $payment->payment_id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                {{ $payment->payment_method ?? 'Unavailable' }}
                                @if ($payment->requested_membership_type)
                                    <div class="table-subtle">Requested plan: {{ $payment->requested_membership_type }}</div>
                                @endif
                            </td>
                            <td>PHP {{ number_format((float) $payment->amount, 2) }}</td>
                            <td><span class="status-badge {{ strtolower($payment->status ?? 'paid') }}">{{ $payment->status ?? 'Paid' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell">No payments have been recorded for this member yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="pagination">
                {{ $payments->links() }}
            </div>
        @endif
    </section>

    <div class="member-toast" id="memberPaymentToast" hidden></div>

    <div class="payment-modal-backdrop" id="memberPaymentModal" hidden>
        <div class="payment-modal-card">
            <div class="payment-modal-head">
                <div>
                    <h3>Complete Payment Details</h3>
                    <p>Enter the transaction information required for the selected payment method before submitting your membership payment request.</p>
                </div>
                <button type="button" class="modal-close-btn" id="memberPaymentModalClose" aria-label="Close payment details modal">X</button>
            </div>

            <div class="payment-modal-summary">
                <div>
                    <span class="stat-label">Plan</span>
                    <strong id="paymentModalPlan">{{ $member->membership_type }}</strong>
                </div>
                <div>
                    <span class="stat-label">Amount</span>
                    <strong id="paymentModalAmount">PHP {{ number_format($currentPlan['price'], 2) }}</strong>
                </div>
                <div>
                    <span class="stat-label">Method</span>
                    <strong id="paymentModalMethod">{{ $paymentMethods[0] ?? 'GCash' }}</strong>
                </div>
                <div>
                    <span class="stat-label">Processing</span>
                    <strong>Admin review required</strong>
                </div>
            </div>

            <div class="payment-method-panel" id="gcashPaymentPanel">
                <div>
                    <h4>GCash Payment Details</h4>
                    <p>Provide the GCash number used and the reference number from your successful transfer.</p>
                </div>
                <div class="payment-method-grid">
                    <div class="form-field">
                        <label for="gcash_number">GCash number</label>
                        <input id="gcash_number" class="member-input" type="text" inputmode="numeric" placeholder="09XXXXXXXXX">
                    </div>
                    <div class="form-field">
                        <label for="gcash_reference_number">Reference number</label>
                        <input id="gcash_reference_number" class="member-input" type="text" placeholder="GCASH-REF-123456">
                    </div>
                    <div class="form-field">
                        <label for="gcash_proof_image">GCash proof image</label>
                        <input id="gcash_proof_image" class="member-input" type="file" accept="image/*">
                        <small class="field-hint">Attach a screenshot of your GCash transfer slip or confirmation.</small>
                    </div>
                </div>
            </div>

            <div class="payment-method-panel" id="cardPaymentPanel">
                <div>
                    <h4>Card Payment Details</h4>
                    <p>Use the exact cardholder name, card network, last four digits, and the gateway reference number for verification.</p>
                </div>
                <div class="payment-method-grid">
                    <div class="form-field">
                        <label for="card_name">Cardholder name</label>
                        <input id="card_name" class="member-input" type="text" placeholder="Name on card">
                    </div>
                    <div class="form-field">
                        <label for="card_network">Card network</label>
                        <select id="card_network" class="member-input">
                            <option value="">Select network</option>
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="JCB">JCB</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="card_last_four">Last 4 digits</label>
                        <input id="card_last_four" class="member-input" type="text" inputmode="numeric" maxlength="4" placeholder="1234">
                    </div>
                    <div class="form-field">
                        <label for="card_reference_number">Reference number</label>
                        <input id="card_reference_number" class="member-input" type="text" placeholder="CARD-REF-123456">
                    </div>
                </div>
            </div>

            <p class="payment-inline-error" id="memberPaymentModalError" hidden></p>

            <div class="payment-modal-actions">
                <button type="button" class="btn" id="memberPaymentModalCancel">Cancel</button>
                <button type="button" class="btn primary" id="memberPaymentModalSubmit">Submit Payment Request</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.memberPaymentsConfig = {
            subscribeUrl: "{{ route('member.payments.subscribe') }}",
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        };
    </script>
    <script src="{{ asset('js/member-payments.js') }}"></script>
@endsection
