(function () {
    const state = {
        page: 1,
        lastPage: 1,
        search: '',
        status: 'All',
        method: 'All',
    };

    const refs = {
        search: document.getElementById('paymentSearch'),
        statusFilter: document.getElementById('paymentStatusFilter'),
        methodFilter: document.getElementById('paymentMethodFilter'),
        tbody: document.getElementById('paymentsTbody'),
        pageInfo: document.getElementById('paymentsPageInfo'),
        prevBtn: document.getElementById('paymentsPrevBtn'),
        nextBtn: document.getElementById('paymentsNextBtn'),
        statTotalRevenue: document.getElementById('statTotalRevenue'),
        statPendingCount: document.getElementById('statPendingCount'),
        statPendingTotal: document.getElementById('statPendingTotal'),
        statFailedCount: document.getElementById('statFailedCount'),
        toast: document.getElementById('paymentsToast'),
        detailsModal: document.getElementById('paymentDetailsModal'),
        detailsClose: document.getElementById('paymentDetailsClose'),
        detailPaymentId: document.getElementById('detailPaymentId'),
        detailTransactionId: document.getElementById('detailTransactionId'),
        detailMember: document.getElementById('detailMember'),
        detailPlan: document.getElementById('detailPlan'),
        detailAmount: document.getElementById('detailAmount'),
        detailMethod: document.getElementById('detailMethod'),
        detailStatus: document.getElementById('detailStatus'),
        detailDate: document.getElementById('detailDate'),
        detailReferenceWrapper: document.getElementById('detailReferenceWrapper'),
        detailReference: document.getElementById('detailReference'),
        detailGcashWrapper: document.getElementById('detailGcashWrapper'),
        detailGcash: document.getElementById('detailGcash'),
        detailProofWrapper: document.getElementById('detailProofWrapper'),
        detailProof: document.getElementById('detailProof'),
        csrfToken: document.querySelector('meta[name="csrf-token"]'),
    };

    function showToast(message, ok = false) {
        refs.toast.textContent = message;
        refs.toast.style.background = ok ? '#000000' : '#595959';
        refs.toast.classList.add('show');
        window.setTimeout(() => refs.toast.classList.remove('show'), 2500);
    }

    function statusClass(status) {
        const value = (status || '').toLowerCase();
        if (value === 'paid') return 'pill active';
        if (value === 'pending') return 'pill inactive';
        return 'pill suspended';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function buildQueryString() {
        const params = new URLSearchParams();
        params.set('page', String(state.page));
        if (state.search) params.set('search', state.search);
        if (state.status) params.set('status', state.status);
        if (state.method) params.set('method', state.method);
        return params.toString();
    }

    function updateMethodOptions(methods) {
        const current = refs.methodFilter.value || 'All';
        refs.methodFilter.innerHTML = '<option value="All">All methods</option>';
        (methods || []).forEach((method) => {
            const option = document.createElement('option');
            option.value = method;
            option.textContent = method;
            refs.methodFilter.appendChild(option);
        });
        if ([...refs.methodFilter.options].some((option) => option.value === current)) {
            refs.methodFilter.value = current;
        }
    }

    function renderTableRows(items) {
        if (!items.length) {
            refs.tbody.innerHTML = '<tr><td colspan="10">No payments found.</td></tr>';
            return;
        }

        refs.tbody.innerHTML = items.map((payment) => {
            const actionRow = payment.can_approve
                ? `
                    <div class="actions">
                        <button class="icon-btn" type="button" data-payment-action="details" data-payment-id="${payment.payment_id}">Details</button>
                        <button class="icon-btn" type="button" data-payment-action="approve" data-payment-id="${payment.payment_id}">Approve</button>
                        <button class="icon-btn" type="button" data-payment-action="reject" data-payment-id="${payment.payment_id}">Reject</button>
                    </div>
                `
                : `
                    <div class="actions">
                        <button class="icon-btn" type="button" data-payment-action="details" data-payment-id="${payment.payment_id}">Details</button>
                    </div>
                `;

            return `
                <tr data-payment-id="${payment.payment_id}" data-payment-method="${escapeHtml(payment.payment_method)}" data-payment-reference="${escapeHtml(payment.reference_number || '')}" data-payment-gcash-number="${escapeHtml(payment.gcash_number || '')}" data-payment-proof-url="${escapeHtml(payment.gcash_image_url || '')}" data-payment-status="${escapeHtml(payment.status)}" data-payment-date="${escapeHtml(payment.date)}" data-payment-plan="${escapeHtml(payment.requested_membership_type || 'N/A')}" data-payment-amount="${escapeHtml(payment.amount)}" data-payment-member="${escapeHtml(payment.member)}" data-payment-transaction="${escapeHtml(payment.transaction_id)}">
                    <td>P${String(payment.payment_id).padStart(3, '0')}</td>
                    <td>${escapeHtml(payment.transaction_id)}</td>
                    <td>${escapeHtml(payment.member)}</td>
                    <td>${escapeHtml(payment.requested_membership_type || 'N/A')}</td>
                    <td>PHP ${escapeHtml(payment.amount)}</td>
                    <td>${escapeHtml(payment.type)}</td>
                    <td><span class="pill role">${escapeHtml(payment.payment_method)}</span></td>
                    <td><span class="${statusClass(payment.status)}">${escapeHtml(payment.status)}</span></td>
                    <td>${escapeHtml(payment.date)}</td>
                    <td>${actionRow}</td>
                </tr>
            `;
        }).join('');
    }

    function showDetailsModal(row) {
        refs.detailPaymentId.textContent = `P${String(row.dataset.paymentId).padStart(3, '0')}`;
        refs.detailTransactionId.textContent = row.dataset.paymentTransaction || '—';
        refs.detailMember.textContent = row.dataset.paymentMember || '—';
        refs.detailPlan.textContent = row.dataset.paymentPlan || '—';
        refs.detailAmount.textContent = `PHP ${row.dataset.paymentAmount || '0.00'}`;
        refs.detailMethod.textContent = row.dataset.paymentMethod || '—';
        refs.detailStatus.textContent = row.dataset.paymentStatus || '—';
        refs.detailDate.textContent = row.dataset.paymentDate || '—';
        refs.detailReference.textContent = row.dataset.paymentReference || '—';

        const isGcash = row.dataset.paymentMethod === 'GCash';
        if (isGcash) {
            refs.detailGcashWrapper.style.display = 'block';
            refs.detailProofWrapper.style.display = 'block';
            refs.detailGcash.textContent = row.dataset.paymentGcashNumber || 'Missing';
            refs.detailProof.innerHTML = row.dataset.paymentProofUrl
                ? `<a href="${escapeHtml(row.dataset.paymentProofUrl)}" target="_blank" rel="noopener">View proof</a>`
                : '<span class="table-subtle">No proof attached</span>';
        } else {
            refs.detailGcashWrapper.style.display = 'none';
            refs.detailProofWrapper.style.display = 'none';
        }

        refs.detailsModal.hidden = false;
        refs.detailsModal.classList.add('show');
    }

    function hideDetailsModal() {
        refs.detailsModal.classList.remove('show');
        refs.detailsModal.hidden = true;
    }

    async function updatePaymentStatus(paymentId, action) {
        const response = await fetch(`/admin/payments/${paymentId}/${action}`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': refs.csrfToken?.getAttribute('content') || '',
            },
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || 'Unable to update payment status.');
        }

        return payload;
    }

    function render(payload) {
        const payments = payload.payments || {};
        const stats = payload.stats || {};
        renderTableRows(payments.data || []);

        refs.statTotalRevenue.textContent = `PHP ${Number(stats.total_revenue || 0).toFixed(2)}`;
        refs.statPendingCount.textContent = String(stats.pending_count || 0);
        refs.statPendingTotal.textContent = `PHP ${Number(stats.pending_total || 0).toFixed(2)} in total`;
        refs.statFailedCount.textContent = String(stats.failed_count || 0);

        const from = payments.from || 0;
        const to = payments.to || 0;
        const total = payments.total || 0;
        refs.pageInfo.textContent = `Showing ${from} to ${to} of ${total} payments`;

        state.page = payments.current_page || 1;
        state.lastPage = payments.last_page || 1;
        refs.prevBtn.disabled = state.page <= 1;
        refs.nextBtn.disabled = state.page >= state.lastPage;

        updateMethodOptions(payload.methods || []);
    }

    async function fetchPayments() {
        refs.tbody.innerHTML = '<tr><td colspan="12">Loading payments...</td></tr>';

        const response = await fetch(`/admin/payments/data?${buildQueryString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Unable to fetch payments data.');
        }

        const payload = await response.json();
        render(payload);
    }

    function bindEvents() {
        let debounceTimer = null;
        refs.search.addEventListener('input', () => {
            const value = refs.search.value.trim();
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                state.search = value;
                state.page = 1;
                fetchPayments().catch((error) => showToast(error.message));
            }, 250);
        });

        refs.statusFilter.addEventListener('change', () => {
            state.status = refs.statusFilter.value;
            state.page = 1;
            fetchPayments().catch((error) => showToast(error.message));
        });

        refs.methodFilter.addEventListener('change', () => {
            state.method = refs.methodFilter.value;
            state.page = 1;
            fetchPayments().catch((error) => showToast(error.message));
        });

        refs.prevBtn.addEventListener('click', () => {
            if (state.page <= 1) return;
            state.page -= 1;
            fetchPayments().catch((error) => showToast(error.message));
        });

        refs.nextBtn.addEventListener('click', () => {
            if (state.page >= state.lastPage) return;
            state.page += 1;
            fetchPayments().catch((error) => showToast(error.message));
        });

        refs.tbody.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-payment-action]');
            if (!button) return;

            const action = button.dataset.paymentAction;
            const paymentId = button.dataset.paymentId;

            if (action === 'details') {
                const row = button.closest('tr');
                if (!row) return;
                showDetailsModal(row);
                return;
            }

            const approved = action === 'approve';
            const shouldProceed = window.AdminNotifications
                ? await window.AdminNotifications.confirm({
                    title: approved ? 'Approve Payment' : 'Reject Payment',
                    message: approved
                        ? 'This will mark the payment as paid and update the member plan if applicable.'
                        : 'This will reject the payment request and mark it as failed.',
                    confirmText: approved ? 'Approve' : 'Reject',
                })
                : window.confirm(`Do you want to ${action} this payment?`);

            if (!shouldProceed) {
                return;
            }

            button.disabled = true;

            try {
                const payload = await updatePaymentStatus(paymentId, action);
                showToast(payload.message || 'Payment updated.', true);
                window.AdminNotifications?.sync?.();
                fetchPayments().catch((error) => showToast(error.message));
            } catch (error) {
                button.disabled = false;
                showToast(error.message);
            }
        });

        refs.detailsClose?.addEventListener('click', hideDetailsModal);
        refs.detailsModal?.addEventListener('click', (event) => {
            if (event.target === refs.detailsModal) {
                hideDetailsModal();
            }
        });
    }

    bindEvents();
    fetchPayments().catch((error) => showToast(error.message));
})();
