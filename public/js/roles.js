/**
 * MeetSpace Enterprise Suite - User Roles Module JavaScript
 *
 * Handles:
 * - Loading roles via /api/roles
 * - Client-side real-time filtering across name and description
 * - Role creation and editing via modal
 * - Safe deletion with user assignment validation and showAppConfirm dialog
 * - Consistent notification feedback via showAppNotification
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('rolesTableBody')) {
            loadRoles();
            setupRoleSearch();
            setupRoleModal();
            setupRoleUsersModal();
        }
    });

    /* ==========================================================================
       STATE
       ========================================================================== */

    let allRoles = [];
    let currentPage = 1;
    let currentPerPage = 10;
    let currentPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
    let roleSearchDebounceTimer = null;

    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadRoles() {
        const loading = document.getElementById('rolesLoading');
        const error = document.getElementById('rolesError');
        const empty = document.getElementById('rolesEmpty');
        const tableWrapper = document.getElementById('rolesTableWrapper');

        try {
            loading?.classList.remove('d-none');
            error?.classList.add('d-none');
            empty?.classList.add('d-none');

            const searchInput = document.getElementById('roleSearch');
            const search = (searchInput?.value || '').trim();

            const params = new URLSearchParams({
                page: String(currentPage),
                per_page: String(currentPerPage),
            });
            if (search) params.append('search', search);

            const response = await fetch(`/api/roles?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                if (response.status === 403) {
                    throw new Error('Access denied. Administrator privileges required.');
                }
                throw new Error(`HTTP error: ${response.status}`);
            }

            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message || 'Failed to load roles.');
            }

            allRoles = result.data || [];
            currentPagination = {
                page: result.page || currentPage,
                per_page: result.per_page || currentPerPage,
                total: result.total || 0,
                total_pages: result.total_pages || 1,
            };

            loading?.classList.add('d-none');

            if (allRoles.length === 0) {
                tableWrapper?.classList.add('d-none');
                empty?.classList.remove('d-none');
            } else {
                empty?.classList.add('d-none');
                tableWrapper?.classList.remove('d-none');
                renderRolesTable(allRoles);
            }

            if (typeof window.renderPagination === 'function') {
                window.renderPagination(
                    '#rolesPagination',
                    currentPagination,
                    (newPage) => {
                        currentPage = newPage;
                        loadRoles();
                    },
                    (newPerPage) => {
                        currentPerPage = newPerPage;
                        currentPage = 1;
                        loadRoles();
                    }
                );
            }

        } catch (err) {
            console.error('Error loading roles:', err);
            loading?.classList.add('d-none');
            tableWrapper?.classList.add('d-none');
            if (error) {
                error.classList.remove('d-none');
                const span = error.querySelector('span');
                if (span) span.textContent = err.message || 'Unable to load roles.';
            }
        }
    }

    /* ==========================================================================
       FILTER & RENDERING
       ========================================================================== */

    function setupRoleSearch() {
        const searchInput = document.getElementById('roleSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', () => {
            clearTimeout(roleSearchDebounceTimer);
            roleSearchDebounceTimer = setTimeout(() => {
                currentPage = 1;
                loadRoles();
            }, 250);
        });
    }

    function renderRolesTable(roles) {
        const tbody = document.getElementById('rolesTableBody');
        const empty = document.getElementById('rolesEmpty');
        const tableWrapper = document.getElementById('rolesTableWrapper');

        if (!tbody) return;

        if (roles.length === 0) {
            tbody.innerHTML = '';
            tableWrapper?.classList.add('d-none');
            empty?.classList.remove('d-none');
            return;
        }

        empty?.classList.add('d-none');
        tableWrapper?.classList.remove('d-none');

        tbody.innerHTML = roles.map(role => {
            const isAdminRole = (role.id === 1 || (role.name || '').toLowerCase() === 'admin');
            const hasAssignedUsers = (role.user_count && role.user_count > 0);

            // Badges
            const roleBadgeClass = isAdminRole ? 'badge-admin-role' : 'badge-standard-role';
            const userCountBadge = hasAssignedUsers
                ? `<span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle role-users-badge" role="button" tabindex="0" data-action="view-users" data-id="${role.id}" title="Click to view ${role.user_count} assigned user(s)"><i class="bi bi-people me-1"></i>${role.user_count} user${role.user_count === 1 ? '' : 's'}</span>`
                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle role-users-badge role-users-badge-empty" role="button" tabindex="0" data-action="view-users" data-id="${role.id}" title="Click to view assigned users (0)"><i class="bi bi-person-dash me-1"></i>0 users</span>`;

            // Created Date
            const createdDate = role.created_at
                ? new Date(role.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
                : '—';

            // Delete action logic
            let deleteBtnHtml = '';
            if (isAdminRole) {
                deleteBtnHtml = `
                    <button type="button" class="btn-action-delete disabled" disabled title="System Administrator role cannot be deleted" aria-label="System Administrator role protected">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            } else if (hasAssignedUsers) {
                deleteBtnHtml = `
                    <button type="button" class="btn-action-delete" data-id="${role.id}" data-action="delete" title="Cannot delete: currently assigned to ${role.user_count} user(s)" style="opacity: 0.6;">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            } else {
                deleteBtnHtml = `
                    <button type="button" class="btn-action-delete" data-id="${role.id}" data-action="delete" title="Delete role ${escapeHtml(role.name)}">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            }

            return `
                <tr data-role-id="${role.id}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="role-icon-box ${roleBadgeClass}">
                                <i class="bi ${isAdminRole ? 'bi-shield-shaded text-warning' : 'bi-person-badge text-primary'}"></i>
                            </div>
                            <div>
                                <span class="fw-semibold booking-room-name d-block">${escapeHtml(role.name)}</span>
                                ${isAdminRole ? '<span class="badge bg-warning-subtle text-warning" style="font-size: 10px; font-weight: 600;">SYSTEM CORE</span>' : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-secondary" style="font-size: 13.5px;">
                            ${role.description ? escapeHtml(role.description) : '<span class="text-muted fst-italic">No description provided</span>'}
                        </span>
                    </td>
                    <td>
                        ${userCountBadge}
                    </td>
                    <td>
                        <span class="text-muted" style="font-size: 13px;">${createdDate}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn-action-edit" data-id="${role.id}" data-action="edit" title="Edit role">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${deleteBtnHtml}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        attachRowEventListeners();
    }

    function attachRowEventListeners() {
        const tbody = document.getElementById('rolesTableBody');
        if (!tbody) return;

        // Assigned Users Badge (only badge triggers modal, not row)
        tbody.querySelectorAll('[data-action="view-users"]').forEach(badge => {
            badge.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = parseInt(badge.dataset.id, 10);
                const role = allRoles.find(r => r.id === id);
                if (role) openAssignedUsersModal(role);
            });

            badge.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = parseInt(badge.dataset.id, 10);
                    const role = allRoles.find(r => r.id === id);
                    if (role) openAssignedUsersModal(role);
                }
            });
        });

        // Edit
        tbody.querySelectorAll('[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id, 10);
                const role = allRoles.find(r => r.id === id);
                if (role) openRoleModal('edit', role);
            });
        });

        // Delete
        tbody.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id, 10);
                const role = allRoles.find(r => r.id === id);
                if (role) confirmDeleteRole(role);
            });
        });
    }

    /* ==========================================================================
       MODAL & FORM
       ========================================================================== */

    function setupRoleModal() {
        const newBtn = document.getElementById('newRoleBtn');
        const modal = document.getElementById('roleModal');
        const closeBtn = document.getElementById('closeRoleModal');
        const cancelBtn = document.getElementById('cancelRoleBtn');
        const form = document.getElementById('roleForm');

        newBtn?.addEventListener('click', () => {
            openRoleModal('create');
        });

        closeBtn?.addEventListener('click', closeRoleModal);
        cancelBtn?.addEventListener('click', closeRoleModal);

        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeRoleModal();
        });

        form?.addEventListener('submit', handleFormSubmit);

        // Input clearing
        const nameInput = document.getElementById('roleName');
        nameInput?.addEventListener('input', () => {
            nameInput.classList.remove('is-invalid');
            const errEl = document.getElementById('roleNameFeedback');
            if (errEl) errEl.classList.add('d-none');
            hideFormError();
        });
    }

    function openRoleModal(mode, role = null) {
        const modal = document.getElementById('roleModal');
        const title = document.getElementById('roleModalTitle');
        const subtitle = document.getElementById('roleModalSubtitle');
        const idInput = document.getElementById('roleId');
        const nameInput = document.getElementById('roleName');
        const descInput = document.getElementById('roleDescription');
        const submitText = document.getElementById('saveRoleBtnText');

        if (!modal) return;

        hideFormError();
        nameInput?.classList.remove('is-invalid');
        const errEl = document.getElementById('roleNameFeedback');
        if (errEl) errEl.classList.add('d-none');

        if (mode === 'edit' && role) {
            title.textContent = 'Edit User Role';
            subtitle.textContent = `Update details for "${role.name}".`;
            submitText.textContent = 'Save Changes';
            idInput.value = role.id;
            nameInput.value = role.name || '';
            descInput.value = role.description || '';
        } else {
            title.textContent = 'New User Role';
            subtitle.textContent = 'Define a new organizational role with custom permissions.';
            submitText.textContent = 'Create Role';
            idInput.value = '';
            nameInput.value = '';
            descInput.value = '';
        }

        modal.classList.remove('d-none');
        setTimeout(() => nameInput?.focus(), 100);
    }

    function closeRoleModal() {
        const modal = document.getElementById('roleModal');
        modal?.classList.add('d-none');
    }

    function showFormError(message) {
        const errorAlert = document.getElementById('roleFormError');
        const errorText = document.getElementById('roleFormErrorText');
        if (errorAlert && errorText) {
            errorText.textContent = message;
            errorAlert.classList.remove('d-none');
        }
    }

    function hideFormError() {
        const errorAlert = document.getElementById('roleFormError');
        errorAlert?.classList.add('d-none');
    }

    async function handleFormSubmit(e) {
        e.preventDefault();

        const idInput = document.getElementById('roleId');
        const nameInput = document.getElementById('roleName');
        const descInput = document.getElementById('roleDescription');
        const saveBtn = document.getElementById('saveRoleBtn');
        const spinner = document.getElementById('saveRoleBtnSpinner');
        const btnText = document.getElementById('saveRoleBtnText');

        const roleId = idInput?.value ? parseInt(idInput.value, 10) : null;
        const name = (nameInput?.value || '').trim();
        const description = (descInput?.value || '').trim();

        // Client-side Validation
        if (!name) {
            nameInput?.classList.add('is-invalid');
            const errEl = document.getElementById('roleNameFeedback');
            if (errEl) {
                errEl.textContent = 'Role name is required.';
                errEl.classList.remove('d-none');
            }
            nameInput?.focus();
            return;
        }

        if (name.length < 2 || name.length > 50) {
            nameInput?.classList.add('is-invalid');
            const errEl = document.getElementById('roleNameFeedback');
            if (errEl) {
                errEl.textContent = 'Role name must be between 2 and 50 characters.';
                errEl.classList.remove('d-none');
            }
            nameInput?.focus();
            return;
        }

        // Duplicate check on client
        const existing = allRoles.find(r => r.name.toLowerCase() === name.toLowerCase() && r.id !== roleId);
        if (existing) {
            nameInput?.classList.add('is-invalid');
            showFormError('A role with this name already exists.');
            nameInput?.focus();
            return;
        }

        const isEdit = Boolean(roleId);
        const url = isEdit ? `/api/roles/${roleId}` : '/api/roles';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            saveBtn.disabled = true;
            spinner?.classList.remove('d-none');

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: name,
                    description: description
                })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                const errorMsg = result.message || (result.errors ? Object.values(result.errors)[0] : 'Failed to save role.');
                showFormError(errorMsg);
                return;
            }

            closeRoleModal();

            if (typeof showAppNotification === 'function') {
                showAppNotification(
                    result.message || (isEdit ? 'Role updated successfully.' : 'Role created successfully.'),
                    'success',
                    'User Roles'
                );
            }

            await loadRoles();

        } catch (err) {
            console.error('Save role error:', err);
            showFormError('Network error while saving role. Please try again.');
        } finally {
            saveBtn.disabled = false;
            spinner?.classList.add('d-none');
        }
    }

    /* ==========================================================================
       DELETE CONFIRMATION & EXECUTION
       ========================================================================== */

    function confirmDeleteRole(role) {
        if (role.id === 1 || (role.name || '').toLowerCase() === 'admin') {
            if (typeof showAppNotification === 'function') {
                showAppNotification('The core system Administrator role cannot be deleted.', 'warning', 'Role Protected');
            } else {
                alert('The core system Administrator role cannot be deleted.');
            }
            return;
        }

        if (role.user_count && role.user_count > 0) {
            const msg = `Cannot delete role "${role.name}" because it is currently assigned to ${role.user_count} user(s). Please reassign those users before deleting.`;
            if (typeof showAppNotification === 'function') {
                showAppNotification(msg, 'error', 'Role In Use');
            } else {
                alert(msg);
            }
            return;
        }

        const confirmMsg = `Are you sure you want to delete the role "${role.name}"? This action cannot be undone.`;

        if (typeof showAppConfirm === 'function') {
            showAppConfirm(
                'Delete Role',
                confirmMsg,
                'Delete Role',
                () => executeDeleteRole(role.id, role.name)
            );
        } else {
            if (window.confirm(confirmMsg)) {
                executeDeleteRole(role.id, role.name);
            }
        }
    }

    async function executeDeleteRole(id, name) {
        try {
            const response = await fetch(`/api/roles/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                const msg = result.message || 'Unable to delete role.';
                if (typeof showAppNotification === 'function') {
                    showAppNotification(msg, 'error', 'Delete Failed');
                } else {
                    alert(msg);
                }
                return;
            }

            if (typeof showAppNotification === 'function') {
                showAppNotification(
                    result.message || `Role "${name}" deleted successfully.`,
                    'success',
                    'Role Deleted'
                );
            }

            await loadRoles();

        } catch (err) {
            console.error('Delete role error:', err);
            if (typeof showAppNotification === 'function') {
                showAppNotification('Network error while deleting role. Please try again.', 'error', 'Error');
            } else {
                alert('Network error while deleting role.');
            }
        }
    }

    /* ==========================================================================
       ASSIGNED USERS MODAL
       ========================================================================== */

    let currentRoleUsersRequestId = 0;

    function setupRoleUsersModal() {
        const modal = document.getElementById('roleUsersModal');
        const closeBtn = document.getElementById('closeRoleUsersModal');
        const closeFooterBtn = document.getElementById('closeRoleUsersFooterBtn');

        if (!modal) return;

        closeBtn?.addEventListener('click', closeRoleUsersModal);
        closeFooterBtn?.addEventListener('click', closeRoleUsersModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeRoleUsersModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('d-none')) {
                closeRoleUsersModal();
            }
        });
    }

    async function openAssignedUsersModal(role) {
        const modal = document.getElementById('roleUsersModal');
        const roleNameEl = document.getElementById('roleUsersRoleName');
        const countBadgeEl = document.getElementById('roleUsersCountBadge');
        const subtitleEl = document.getElementById('roleUsersModalSubtitle');
        const loadingEl = document.getElementById('roleUsersLoading');
        const errorEl = document.getElementById('roleUsersError');
        const errorTextEl = document.getElementById('roleUsersErrorText');
        const emptyEl = document.getElementById('roleUsersEmpty');
        const listEl = document.getElementById('roleUsersList');

        if (!modal || !role) return;

        const requestId = ++currentRoleUsersRequestId;
        const roleId = Number(role.id);
        const initialCount = Number(role.user_count || 0);

        if (roleNameEl) roleNameEl.textContent = role.name || '—';
        if (subtitleEl) subtitleEl.textContent = `Users currently assigned to the ${role.name} role.`;
        if (countBadgeEl) countBadgeEl.textContent = String(initialCount);

        errorEl?.classList.add('d-none');
        emptyEl?.classList.add('d-none');
        if (listEl) {
            listEl.innerHTML = '';
            listEl.classList.add('d-none');
        }
        loadingEl?.classList.remove('d-none');

        modal.classList.remove('d-none');

        try {
            const params = new URLSearchParams({
                role_id: String(roleId),
                all: '1'
            });

            const response = await fetch(`/api/users?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (requestId !== currentRoleUsersRequestId) return;

            if (!response.ok) {
                throw new Error(`Unable to retrieve assigned users (HTTP ${response.status}).`);
            }

            const result = await response.json();
            if (requestId !== currentRoleUsersRequestId) return;

            if (result.status !== 'success') {
                throw new Error(result.message || 'Unable to retrieve assigned users.');
            }

            // Strictly filter by role_id to guarantee only users for the selected role are shown
            const rawUsers = Array.isArray(result.data) ? result.data : [];
            const assignedUsers = rawUsers.filter(u => Number(u.role_id) === roleId);

            loadingEl?.classList.add('d-none');

            if (countBadgeEl) {
                countBadgeEl.textContent = String(assignedUsers.length);
            }

            if (assignedUsers.length === 0) {
                emptyEl?.classList.remove('d-none');
                return;
            }

            if (listEl) {
                listEl.innerHTML = assignedUsers.map((u) => {
                    const firstName = (u.first_name || '').trim();
                    const lastName = (u.last_name || '').trim();
                    const fullName = `${firstName} ${lastName}`.trim() || (u.email || `User #${u.id}`);
                    const email = (u.email || '').trim();
                    const initials = getInitials(firstName, lastName, email);

                    return `
                        <li class="role-user-item">
                            <div class="role-user-avatar" aria-hidden="true">${escapeHtml(initials)}</div>
                            <div class="role-user-info">
                                <div class="role-user-name">${escapeHtml(fullName)}</div>
                                <div class="role-user-email">${escapeHtml(email)}</div>
                            </div>
                        </li>
                    `;
                }).join('');

                listEl.classList.remove('d-none');
            }
        } catch (err) {
            if (requestId !== currentRoleUsersRequestId) return;
            console.error('Error loading assigned users for role:', err);
            loadingEl?.classList.add('d-none');
            if (errorEl) {
                if (errorTextEl) {
                    errorTextEl.textContent = err.message || 'Unable to load assigned users.';
                }
                errorEl.classList.remove('d-none');
            }
        }
    }

    function closeRoleUsersModal() {
        const modal = document.getElementById('roleUsersModal');
        currentRoleUsersRequestId++;
        modal?.classList.add('d-none');
    }

    function getInitials(firstName, lastName, email) {
        const f = (firstName || '').charAt(0).toUpperCase();
        const l = (lastName || '').charAt(0).toUpperCase();
        if (f && l) return `${f}${l}`;
        if (f) return f;
        if (email) return email.charAt(0).toUpperCase();
        return 'U';
    }

    /* ==========================================================================
       UTILITIES
       ========================================================================== */

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})();
