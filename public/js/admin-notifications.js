(function () {
    const STORAGE_KEY = 'wedumbell_admin_notifications';
    const MAX_ITEMS = 20;
    let systemNotifications = [];

    const refs = {
        toggle: document.getElementById('adminNotificationToggle'),
        count: document.getElementById('adminNotificationCount'),
        panel: document.getElementById('adminNotificationPanel'),
        list: document.getElementById('adminNotificationList'),
        clear: document.getElementById('adminNotificationClear'),
        confirmModal: document.getElementById('adminConfirmModal'),
        confirmTitle: document.getElementById('adminConfirmTitle'),
        confirmMessage: document.getElementById('adminConfirmMessage'),
        confirmCancel: document.getElementById('adminConfirmCancel'),
        confirmAccept: document.getElementById('adminConfirmAccept'),
    };

    let confirmResolver = null;

    function loadNotifications() {
        try {
            return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (_error) {
            return [];
        }
    }

    function saveNotifications(items) {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0, MAX_ITEMS)));
    }

    function relativeTime(value) {
        const diffMs = Date.now() - new Date(value).getTime();
        const minutes = Math.max(1, Math.round(diffMs / 60000));

        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.round(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.round(hours / 24);
        return `${days}d ago`;
    }

    function mergedNotifications() {
        const manualItems = loadNotifications();
        return [...systemNotifications, ...manualItems]
            .sort((left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime())
            .slice(0, MAX_ITEMS);
    }

    function renderNotifications() {
        if (!refs.list || !refs.count) {
            return;
        }

        const items = mergedNotifications();
        refs.count.textContent = String(items.length);

        if (!items.length) {
            refs.list.innerHTML = '<div class="notification-empty">No notifications yet.</div>';
            return;
        }

        refs.list.innerHTML = items.map((item) => `
            <article class="notification-item ${item.type || 'info'}">
                <strong>${escapeHtml(item.title)}</strong>
                <p>${escapeHtml(item.message)}</p>
                <time datetime="${escapeHtml(item.created_at)}">${escapeHtml(relativeTime(item.created_at))}</time>
            </article>
        `).join('');
    }

    async function syncSystemNotifications() {
        try {
            const response = await fetch('/admin/notifications/data', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            systemNotifications = payload?.items || [];

            renderNotifications();
        } catch (_error) {
            // Keep local notifications available even if live sync fails.
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function addNotification(notification) {
        const items = loadNotifications();
        items.unshift({
            title: notification.title || 'Admin update',
            message: notification.message || '',
            type: notification.type || 'info',
            created_at: new Date().toISOString(),
        });
        saveNotifications(items);
        renderNotifications();
    }

    function togglePanel(forceState) {
        if (!refs.panel || !refs.toggle) {
            return;
        }

        const shouldOpen = typeof forceState === 'boolean'
            ? forceState
            : refs.panel.hasAttribute('hidden');

        if (shouldOpen) {
            refs.panel.removeAttribute('hidden');
            refs.toggle.setAttribute('aria-expanded', 'true');
        } else {
            refs.panel.setAttribute('hidden', '');
            refs.toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function confirmAction(options) {
        if (!refs.confirmModal) {
            return Promise.resolve(window.confirm(options.message || 'Are you sure?'));
        }

        refs.confirmTitle.textContent = options.title || 'Please Confirm';
        refs.confirmMessage.textContent = options.message || 'Are you sure you want to continue?';
        refs.confirmAccept.textContent = options.confirmText || 'Confirm';
        refs.confirmModal.classList.add('show');

        return new Promise((resolve) => {
            confirmResolver = resolve;
        });
    }

    function settleConfirm(result) {
        if (refs.confirmModal) {
            refs.confirmModal.classList.remove('show');
        }

        if (confirmResolver) {
            confirmResolver(result);
            confirmResolver = null;
        }
    }

    function bindEvents() {
        refs.toggle?.addEventListener('click', () => togglePanel());
        refs.clear?.addEventListener('click', () => {
            saveNotifications([]);
            renderNotifications();
        });

        refs.confirmCancel?.addEventListener('click', () => settleConfirm(false));
        refs.confirmAccept?.addEventListener('click', () => settleConfirm(true));
        refs.confirmModal?.addEventListener('click', (event) => {
            if (event.target === refs.confirmModal) {
                settleConfirm(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                togglePanel(false);
                if (refs.confirmModal?.classList.contains('show')) {
                    settleConfirm(false);
                }
            }
        });

        document.addEventListener('click', (event) => {
            if (!refs.panel || !refs.toggle || refs.panel.hasAttribute('hidden')) {
                return;
            }

            const insidePanel = refs.panel.contains(event.target);
            const insideToggle = refs.toggle.contains(event.target);

            if (!insidePanel && !insideToggle) {
                togglePanel(false);
            }
        });
    }

    window.AdminNotifications = {
        add: addNotification,
        confirm: confirmAction,
        refresh: renderNotifications,
        sync: syncSystemNotifications,
    };

    bindEvents();
    renderNotifications();
    syncSystemNotifications();
})();
