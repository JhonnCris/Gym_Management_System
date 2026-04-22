(function () {
    const config = window.memberClassesConfig;
    const buttons = Array.from(document.querySelectorAll('.member-booking-action'));
    const toast = document.getElementById('memberClassToast');
    const modal = document.getElementById('cancelBookingModal');
    const modalClose = document.getElementById('cancelBookingModalClose');
    const modalCancel = document.getElementById('cancelBookingModalCancel');
    const modalConfirm = document.getElementById('cancelBookingModalConfirm');
    const modalReason = document.getElementById('cancelBookingReason');
    const modalError = document.getElementById('cancelBookingModalError');
    const modalClassName = document.getElementById('cancelBookingClassName');
    const modalSchedule = document.getElementById('cancelBookingSchedule');
    const modalTrainer = document.getElementById('cancelBookingTrainer');

    if (!config || !buttons.length || !toast || !modal || !modalConfirm || !modalReason) {
        return;
    }

    let toastTimer = null;
    let activeBookingId = null;
    let activeBookingButton = null;

    const setToast = (message, isError = false) => {
        toast.textContent = message;
        toast.classList.toggle('error', isError);
        toast.hidden = false;

        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 2600);
    };

    const setBusy = (button, busy) => {
        button.disabled = busy;
        button.dataset.busy = busy ? 'true' : 'false';
    };

    const setModalError = (message = '') => {
        modalError.textContent = message;
        modalError.hidden = !message;
    };

    const request = async (url, options) => {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || 'Unable to update booking.');
        }

        return payload;
    };

    const resetModal = () => {
        modalReason.value = '';
        setModalError('');
        activeBookingButton = null;
        activeBookingId = null;
    };

    const openModal = ({ bookingId, className, schedule, trainer, button }) => {
        activeBookingId = bookingId;
        activeBookingButton = button;
        modalClassName.textContent = className;
        modalSchedule.textContent = schedule;
        modalTrainer.textContent = trainer;
        modal.hidden = false;
        modal.classList.add('show');
        setModalError('');
        modalReason.focus();
    };

    const closeModal = () => {
        modal.classList.remove('show');
        modal.hidden = true;
        modalConfirm.disabled = false;
        resetModal();
    };

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (button.disabled) {
                return;
            }

            if (button.dataset.action === 'cancel') {
                openModal({
                    bookingId: button.dataset.bookingId,
                    className: button.dataset.className || 'Booked class',
                    schedule: button.dataset.schedule || 'Schedule TBD',
                    trainer: button.dataset.trainer || 'Trainer unassigned',
                    button,
                });
                return;
            }

            setBusy(button, true);

            try {
                if (button.dataset.action === 'book') {
                    await request('/api/v1/bookings', {
                        method: 'POST',
                        body: JSON.stringify({
                            member_id: config.memberId,
                            class_id: Number(button.dataset.classId),
                            status: 'Booked',
                        }),
                    });

                    setToast('Class booked successfully.');
                }

                window.setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                setToast(error.message, true);
                setBusy(button, false);
            }
        });
    });

    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    modalConfirm?.addEventListener('click', async () => {
        const reason = modalReason.value.trim();

        if (!reason) {
            setModalError('Please provide a reason for cancellation.');
            modalReason.focus();
            return;
        }

        setModalError('');
        modalConfirm.disabled = true;

        try {
            await request(`/api/v1/bookings/${activeBookingId}`, {
                method: 'PATCH',
                body: JSON.stringify({
                    status: 'Cancelled',
                    cancellation_reason: reason,
                }),
            });

            setToast('Booking cancelled successfully.');

            if (activeBookingButton) {
                const bookingCard = activeBookingButton.closest('.booking-card');
                const statusBadge = bookingCard?.querySelector('.status-badge');

                if (statusBadge) {
                    statusBadge.textContent = 'Cancelled';
                    statusBadge.classList.remove('active');
                    statusBadge.classList.add('cancelled');
                }

                activeBookingButton.textContent = 'Cancelled';
                activeBookingButton.disabled = true;
                activeBookingButton.classList.remove('subtle');
                activeBookingButton.classList.add('danger');
            }

            closeModal();
        } catch (error) {
            setModalError(error.message);
            modalConfirm.disabled = false;
        }
    });
})();
