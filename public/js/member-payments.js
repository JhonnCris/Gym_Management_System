(function () {
    const config = window.memberPaymentsConfig;
    const planCards = Array.from(document.querySelectorAll('[data-plan-card]'));
    const form = document.getElementById('memberSubscriptionForm');
    const planInput = document.getElementById('memberPlanInput');
    const planLabel = document.getElementById('selectedPlanLabel');
    const planPrice = document.getElementById('selectedPlanPrice');
    const submitButton = document.getElementById('memberSubscribeBtn');
    const toast = document.getElementById('memberPaymentToast');
    const paymentMethod = document.getElementById('payment_method');
    const modal = document.getElementById('memberPaymentModal');
    const modalClose = document.getElementById('memberPaymentModalClose');
    const modalCancel = document.getElementById('memberPaymentModalCancel');
    const modalSubmit = document.getElementById('memberPaymentModalSubmit');
    const modalMethod = document.getElementById('paymentModalMethod');
    const modalPlan = document.getElementById('paymentModalPlan');
    const modalAmount = document.getElementById('paymentModalAmount');
    const modalError = document.getElementById('memberPaymentModalError');
    const gcashPanel = document.getElementById('gcashPaymentPanel');
    const cardPanel = document.getElementById('cardPaymentPanel');
    const gcashNumber = document.getElementById('gcash_number');
    const gcashReference = document.getElementById('gcash_reference_number');
    const gcashProof = document.getElementById('gcash_proof_image');
    const cardName = document.getElementById('card_name');
    const cardNetwork = document.getElementById('card_network');
    const cardLastFour = document.getElementById('card_last_four');
    const cardReference = document.getElementById('card_reference_number');

    if (!config || !form || !planInput || !planLabel || !planPrice || !submitButton || !toast || !paymentMethod || !modal || !modalSubmit) {
        return;
    }

    let toastTimer = null;

    const showToast = (message, isError = false) => {
        toast.textContent = message;
        toast.classList.toggle('error', isError);
        toast.hidden = false;

        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 2600);
    };

    const setModalError = (message = '') => {
        modalError.textContent = message;
        modalError.hidden = !message;
    };

    const updatePaymentPanels = () => {
        const isGcash = paymentMethod.value === 'GCash';
        gcashPanel.classList.toggle('show', isGcash);
        cardPanel.classList.toggle('show', !isGcash);
        modalMethod.textContent = paymentMethod.value;
        modalPlan.textContent = planInput.value;
        modalAmount.textContent = planPrice.textContent;
    };

    const openModal = () => {
        updatePaymentPanels();
        setModalError('');
        modal.hidden = false;
        modal.classList.add('show');
    };

    const closeModal = () => {
        modal.classList.remove('show');
        modal.hidden = true;
        submitButton.disabled = false;
        modalSubmit.disabled = false;
        setModalError('');
    };

    const collectPaymentDetails = () => {
        if (paymentMethod.value === 'GCash') {
            if (!gcashNumber.value.trim() || !gcashReference.value.trim()) {
                throw new Error('Enter both the GCash number and reference number.');
            }
            if (!gcashProof?.files?.length) {
                throw new Error('Attach a proof image for your GCash payment.');
            }

            return {
                reference_number: gcashReference.value.trim(),
                gcash_number: gcashNumber.value.trim(),
                gcash_proof_image: gcashProof.files[0],
                card_name: '',
                card_last_four: '',
                card_network: '',
            };
        }

        if (!cardName.value.trim() || !cardNetwork.value || !/^\d{4}$/.test(cardLastFour.value.trim()) || !cardReference.value.trim()) {
            throw new Error('Complete the cardholder name, network, last 4 digits, and reference number.');
        }

        return {
            reference_number: cardReference.value.trim(),
            gcash_number: '',
            card_name: cardName.value.trim(),
            card_last_four: cardLastFour.value.trim(),
            card_network: cardNetwork.value,
        };
    };

    const syncSelection = (card) => {
        planCards.forEach((item) => item.classList.remove('selected'));
        card.classList.add('selected');
        planInput.value = card.dataset.planName;
        planLabel.textContent = card.dataset.planName;
        planPrice.textContent = `PHP ${Number(card.dataset.planPrice).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        updatePaymentPanels();
    };

    planCards.forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('button') || event.target.closest('[data-select-plan]')) {
                event.preventDefault();
            }

            syncSelection(card);
        });
    });

    const selected = planCards.find((card) => card.dataset.planName === planInput.value) || planCards[0];
    if (selected) {
        syncSelection(selected);
    }

    paymentMethod.addEventListener('change', updatePaymentPanels);
    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    submitButton.addEventListener('click', (event) => {
        event.preventDefault();
        submitButton.disabled = true;
        openModal();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
    });

    modalSubmit.addEventListener('click', async () => {
        modalSubmit.disabled = true;
        setModalError('');

        try {
            const details = collectPaymentDetails();
            const body = new FormData();
            body.append('membership_type', planInput.value);
            body.append('payment_method', paymentMethod.value);
            body.append('reference_number', details.reference_number);
            if (details.gcash_number) {
                body.append('gcash_number', details.gcash_number);
            }
            if (details.gcash_proof_image) {
                body.append('gcash_proof_image', details.gcash_proof_image);
            }
            if (details.card_name) {
                body.append('card_name', details.card_name);
            }
            if (details.card_last_four) {
                body.append('card_last_four', details.card_last_four);
            }
            if (details.card_network) {
                body.append('card_network', details.card_network);
            }

            const response = await fetch(config.subscribeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Unable to update subscription.');
            }

            showToast(payload.message || 'Subscription updated successfully.');
            closeModal();
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            setModalError(error.message);
            showToast(error.message, true);
            modalSubmit.disabled = false;
        }
    });
})();
