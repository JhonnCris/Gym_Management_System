(function () {
    const state = {
        page: 1,
        lastPage: 1,
        search: '',
        role: 'All',
        status: 'All',
        editingUserId: null,
    };

    const refs = {
        tbody: document.getElementById('usersTbody'),
        pageInfo: document.getElementById('pageInfo'),
        prevPageBtn: document.getElementById('prevPageBtn'),
        nextPageBtn: document.getElementById('nextPageBtn'),
        searchInput: document.getElementById('searchInput'),
        userSuggestions: document.getElementById('userSuggestions'),
        roleFilter: document.getElementById('roleFilter'),
        statusFilter: document.getElementById('statusFilter'),
        statActive: document.getElementById('statActive'),
        statInactive: document.getElementById('statInactive'),
        statSuspended: document.getElementById('statSuspended'),
        addUserBtn: document.getElementById('addUserBtn'),
        userModal: document.getElementById('userModal'),
        userForm: document.getElementById('userForm'),
        modalTitle: document.getElementById('modalTitle'),
        cancelBtn: document.getElementById('cancelBtn'),
        userId: document.getElementById('userId'),
        fullName: document.getElementById('fullName'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        password: document.getElementById('password'),
        passwordConfirmation: document.getElementById('passwordConfirmation'),
        passwordField: document.getElementById('passwordField'),
        passwordConfirmationField: document.getElementById('passwordConfirmationField'),
        passwordMismatchError: document.getElementById('passwordMismatchError'),
        role: document.getElementById('role'),
        membershipType: document.getElementById('membershipType'),
        membershipTypeField: document.getElementById('membershipTypeField'),
        status: document.getElementById('status'),
        joinDate: document.getElementById('joinDate'),
        expiryDate: document.getElementById('expiryDate'),
        saveBtn: document.getElementById('saveBtn'),
        toast: document.getElementById('toast'),
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function toast(message, ok) {
        refs.toast.textContent = message;
        refs.toast.style.background = ok ? '#106c2e' : '#8b1a1a';
        refs.toast.classList.add('show');
        window.setTimeout(() => refs.toast.classList.remove('show'), 2600);
    }

    function toggleMemberFields() {
        const isMember = refs.role.value === 'Member';
        refs.membershipTypeField.style.display = isMember ? 'block' : 'none';
        refs.membershipType.required = isMember;
    }

    function togglePasswordFields(isEdit) {
        refs.passwordField.style.display = isEdit ? 'none' : 'block';
        refs.passwordConfirmationField.style.display = isEdit ? 'none' : 'block';
        refs.password.required = !isEdit;
        refs.passwordConfirmation.required = !isEdit;

        if (isEdit) {
            refs.password.value = '';
            refs.passwordConfirmation.value = '';
        }

        clearPasswordErrorState();
    }

    function setPasswordErrorState(message) {
        const hasError = Boolean(message);
        refs.password.classList.toggle('input-invalid', hasError);
        refs.passwordConfirmation.classList.toggle('input-invalid', hasError);
        refs.passwordMismatchError.style.display = hasError ? 'block' : 'none';
        refs.passwordMismatchError.textContent = message || '';
    }

    function clearPasswordErrorState() {
        setPasswordErrorState('');
    }

    function validatePasswordFields() {
        if (state.editingUserId) {
            clearPasswordErrorState();
            return true;
        }

        const password = refs.password.value;
        const confirmation = refs.passwordConfirmation.value;

        if (!password && !confirmation) {
            setPasswordErrorState('Password is required.');
            return false;
        }

        if (password.length > 0 && password.length < 8) {
            setPasswordErrorState('Password must be at least 8 characters long.');
            return false;
        }

        if (confirmation && password !== confirmation) {
            setPasswordErrorState('Passwords do not match.');
            return false;
        }

        clearPasswordErrorState();
        return true;
    }

    function buildParams() {
        const params = new URLSearchParams();
        params.set('page', String(state.page));
        if (state.search) params.set('search', state.search);
        if (state.role) params.set('role', state.role);
        if (state.status) params.set('status', state.status);
        return params.toString();
    }

    function statusPillClass(status) {
        const lower = (status || '').toLowerCase();
        if (lower === 'active') return 'pill active';
        if (lower === 'inactive') return 'pill inactive';
        return 'pill suspended';
    }

    function fillStats(stats) {
        refs.statActive.textContent = stats.active ?? 0;
        refs.statInactive.textContent = stats.inactive ?? 0;
        refs.statSuspended.textContent = stats.suspended ?? 0;
    }

    function rowHtml(user) {
        const membership = user.membership_type || 'N/A';
        const lastVisit = user.last_visit || 'No visit yet';
        const expiry = user.expiry_date || 'N/A';

        return `
            <tr>
                <td>M${String(user.id).padStart(3, '0')}</td>
                <td>${escapeHtml(user.full_name || '')}</td>
                <td>
                    <div>${escapeHtml(user.email || '')}</div>
                    <small>${escapeHtml(user.phone || 'No phone')}</small>
                </td>
                <td><span class="pill role">${escapeHtml(user.role || '')}</span></td>
                <td>${escapeHtml(membership)}</td>
                <td><span class="${statusPillClass(user.status)}">${escapeHtml(user.status || '')}</span></td>
                <td>${escapeHtml(user.join_date || 'N/A')}</td>
                <td>${escapeHtml(expiry)}</td>
                <td>${escapeHtml(lastVisit)}</td>
                <td>
                    <div class="actions">
                        <button class="icon-btn edit-btn" data-id="${user.id}">Edit</button>
                        <button class="icon-btn delete-btn" data-id="${user.id}">Delete</button>
                        <select class="status-select status-change" data-id="${user.id}">
                            <option value="Active" ${user.status === 'Active' ? 'selected' : ''}>Active</option>
                            <option value="Inactive" ${user.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                            <option value="Suspended" ${user.status === 'Suspended' ? 'selected' : ''}>Suspended</option>
                        </select>
                    </div>
                </td>
            </tr>
        `;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function renderUsers(payload) {
        const users = payload.users.data || [];

        if (!users.length) {
            refs.tbody.innerHTML = '<tr><td colspan="10">No users found.</td></tr>';
        } else {
            refs.tbody.innerHTML = users.map(rowHtml).join('');
        }

        fillStats(payload.stats || {});

        const from = payload.users.from || 0;
        const to = payload.users.to || 0;
        const total = payload.users.total || 0;
        refs.pageInfo.textContent = `Showing ${from} to ${to} of ${total} users`;

        state.page = payload.users.current_page || 1;
        state.lastPage = payload.users.last_page || 1;
        refs.prevPageBtn.disabled = state.page <= 1;
        refs.nextPageBtn.disabled = state.page >= state.lastPage;
    }

    async function fetchSuggestions(searchTerm) {
        if (!searchTerm) {
            refs.userSuggestions.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`/admin/users/suggestions?search=${encodeURIComponent(searchTerm)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const suggestions = payload.suggestions || [];

            refs.userSuggestions.innerHTML = suggestions
                .map((item) => `<option value="${escapeHtml(item.value)}"></option>`)
                .join('');
        } catch (_error) {
            // Keep UI resilient: ignore transient suggestion fetch issues.
        }
    }

    async function fetchUsers() {
        refs.tbody.innerHTML = '<tr><td colspan="10">Loading users...</td></tr>';

        const response = await fetch(`/admin/users/data?${buildParams()}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to load users.');
        }

        const payload = await response.json();
        renderUsers(payload);
    }

    function openModal(editing, user) {
        refs.userForm.reset();
        refs.userId.value = '';
        state.editingUserId = null;

        if (editing && user) {
            refs.modalTitle.textContent = 'Edit User';
            refs.saveBtn.textContent = 'Update User';
            refs.userId.value = String(user.id);
            state.editingUserId = user.id;
            refs.fullName.value = user.full_name || '';
            refs.email.value = user.email || '';
            refs.phone.value = user.phone || '';
            refs.role.value = user.role || 'Member';
            refs.membershipType.value = user.membership_type || '';
            refs.status.value = user.status || 'Active';
            refs.joinDate.value = user.join_date || '';
            refs.expiryDate.value = user.expiry_date || '';
        } else {
            refs.modalTitle.textContent = 'Add New User';
            refs.saveBtn.textContent = 'Save User';
            refs.role.value = 'Member';
            refs.status.value = 'Active';
        }

        toggleMemberFields();
        togglePasswordFields(editing);
        refs.userModal.classList.add('show');
    }

    function closeModal() {
        refs.userModal.classList.remove('show');
    }

    async function submitForm(event) {
        event.preventDefault();

        const payload = {
            full_name: refs.fullName.value.trim(),
            email: refs.email.value.trim(),
            phone: refs.phone.value.trim(),
            role: refs.role.value,
            membership_type: refs.membershipType.value || null,
            status: refs.status.value,
            join_date: refs.joinDate.value || null,
            expiry_date: refs.expiryDate.value || null,
        };

        if (!state.editingUserId) {
            payload.password = refs.password.value;
            payload.password_confirmation = refs.passwordConfirmation.value;
        }

        if (!payload.full_name || !payload.email || !payload.role || !payload.status) {
            toast('Please complete all required fields.', false);
            return;
        }

        if (payload.role === 'Member' && !payload.membership_type) {
            toast('Membership type is required for members.', false);
            return;
        }

        if (!state.editingUserId) {
            if (!payload.password || payload.password.length < 8) {
                setPasswordErrorState('Password must be at least 8 characters long.');
                toast('Password must be at least 8 characters long.', false);
                return;
            }

            if (payload.password !== payload.password_confirmation) {
                setPasswordErrorState('Passwords do not match.');
                toast('Password confirmation does not match.', false);
                return;
            }
        }

        const isEdit = Boolean(state.editingUserId);
        const url = isEdit ? `/admin/users/${state.editingUserId}` : '/admin/users';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                const firstError = data?.message || Object.values(data?.errors || {})?.flat()?.[0] || 'Request failed.';
                throw new Error(firstError);
            }

            toast(data.message || 'Saved successfully.', true);
            if (window.AdminNotifications) {
                window.AdminNotifications.add({
                    title: isEdit ? 'Account updated' : 'New account created',
                    message: isEdit
                        ? `${payload.full_name} was updated successfully.`
                        : `${payload.full_name} was added as a ${payload.role} account.`,
                    type: isEdit ? 'info' : 'success',
                });
            }
            closeModal();
            await fetchUsers();
        } catch (error) {
            toast(error.message, false);
        }
    }

    async function deleteUser(userId) {
        const shouldDelete = window.AdminNotifications
            ? await window.AdminNotifications.confirm({
                title: 'Delete User',
                message: 'This will remove the selected user from the system. Do you want to continue?',
                confirmText: 'Delete',
            })
            : window.confirm('Delete this user?');

        if (!shouldDelete) return;

        try {
            const response = await fetch(`/admin/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data?.message || 'Delete failed.');
            }

            toast(data.message || 'User deleted.', true);
            if (window.AdminNotifications) {
                window.AdminNotifications.add({
                    title: 'Account deleted',
                    message: `User #${userId} was removed from the system.`,
                    type: 'warning',
                });
            }
            await fetchUsers();
        } catch (error) {
            toast(error.message, false);
        }
    }

    async function changeStatus(userId, status) {
        try {
            const response = await fetch(`/admin/users/${userId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ status }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data?.message || 'Status update failed.');
            }

            toast(data.message || 'Status updated.', true);
            if (window.AdminNotifications) {
                window.AdminNotifications.add({
                    title: 'Status changed',
                    message: `User #${userId} is now marked as ${status}.`,
                    type: 'info',
                });
            }
            await fetchUsers();
        } catch (error) {
            toast(error.message, false);
            await fetchUsers();
        }
    }

    async function editUser(userId) {
        try {
            const response = await fetch(`/admin/users/${userId}`, {
                headers: { Accept: 'application/json' },
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data?.message || 'Failed to load user.');
            }

            openModal(true, data.user);
        } catch (error) {
            toast(error.message, false);
        }
    }

    function bindEvents() {
        refs.addUserBtn.addEventListener('click', () => openModal(false));
        refs.cancelBtn.addEventListener('click', closeModal);
        refs.userModal.addEventListener('click', (event) => {
            if (event.target === refs.userModal) {
                closeModal();
            }
        });
        refs.role.addEventListener('change', toggleMemberFields);
        refs.userForm.addEventListener('submit', submitForm);
        refs.password?.addEventListener('input', validatePasswordFields);
        refs.passwordConfirmation?.addEventListener('input', validatePasswordFields);
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const input = document.getElementById(toggle.dataset.passwordToggle);
                if (!input) return;
                const hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                toggle.textContent = hidden ? 'Hide' : 'Show';
                toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
            });
        });

        let searchDebounceTimer = null;
        refs.searchInput.addEventListener('input', () => {
            const term = refs.searchInput.value.trim();

            window.clearTimeout(searchDebounceTimer);
            searchDebounceTimer = window.setTimeout(() => {
                state.search = term;
                state.page = 1;
                fetchSuggestions(term);
                fetchUsers().catch((error) => toast(error.message, false));
            }, 250);
        });

        refs.searchInput.addEventListener('change', () => {
            state.search = refs.searchInput.value.trim();
            state.page = 1;
            fetchUsers().catch((error) => toast(error.message, false));
        });

        refs.roleFilter.addEventListener('change', () => {
            state.role = refs.roleFilter.value;
            state.page = 1;
            fetchUsers().catch((error) => toast(error.message, false));
        });

        refs.statusFilter.addEventListener('change', () => {
            state.status = refs.statusFilter.value;
            state.page = 1;
            fetchUsers().catch((error) => toast(error.message, false));
        });

        refs.prevPageBtn.addEventListener('click', () => {
            if (state.page <= 1) return;
            state.page -= 1;
            fetchUsers().catch((error) => toast(error.message, false));
        });

        refs.nextPageBtn.addEventListener('click', () => {
            if (state.page >= state.lastPage) return;
            state.page += 1;
            fetchUsers().catch((error) => toast(error.message, false));
        });

        refs.tbody.addEventListener('click', (event) => {
            const editBtn = event.target.closest('.edit-btn');
            if (editBtn) {
                const userId = editBtn.getAttribute('data-id');
                editUser(userId);
                return;
            }

            const deleteBtn = event.target.closest('.delete-btn');
            if (deleteBtn) {
                const userId = deleteBtn.getAttribute('data-id');
                deleteUser(userId);
            }
        });

        refs.tbody.addEventListener('change', (event) => {
            const select = event.target.closest('.status-change');
            if (select) {
                const userId = select.getAttribute('data-id');
                changeStatus(userId, select.value);
            }
        });
    }

    bindEvents();
    fetchUsers().catch((error) => toast(error.message, false));
})();
