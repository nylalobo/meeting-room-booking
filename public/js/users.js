/**
 * MeetSpace Enterprise Suite - Users Module JavaScript
 *
 * Handles:
 * - Loading users, roles, and departments via Promise.all
 * - Dynamic enrichment of role and department names
 * - Multi-criteria search and filter toolbar
 * - Add/Edit modal with conditional password requirement
 * - User deletion with custom confirmation dialog
 * - Inline alerts and global application notifications
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (document.getElementById('usersTableBody')) {
            loadUsersData();
            setupUserFilters();
            setupUserModal();
        }
    });


    /* ==========================================================================
       STATE
       ========================================================================== */

    let allUsers = [];
    let allRoles = [];
    let allDepartments = [];
    let rolesMap = new Map();
    let departmentsMap = new Map();


    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadUsersData() {

        const loading =
            document.getElementById(
                'usersLoading'
            );

        const error =
            document.getElementById(
                'usersError'
            );

        const empty =
            document.getElementById(
                'usersEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'usersTableWrapper'
            );

        try {

            loading?.classList.remove(
                'd-none'
            );

            error?.classList.add(
                'd-none'
            );

            empty?.classList.add(
                'd-none'
            );

            tableWrapper?.classList.add(
                'd-none'
            );

            const [
                usersRes,
                rolesRes,
                departmentsRes
            ] = await Promise.all([
                fetch('/api/users'),
                fetch('/roles'),
                fetch('/departments')
            ]);

            if (
                !usersRes.ok ||
                !rolesRes.ok ||
                !departmentsRes.ok
            ) {

                throw new Error(
                    'One or more user data sources failed to load.'
                );
            }

            const [
                usersResult,
                rolesResult,
                departmentsResult
            ] = await Promise.all([
                usersRes.json(),
                rolesRes.json(),
                departmentsRes.json()
            ]);

            allUsers =
                usersResult.data || [];

            allRoles =
                rolesResult.data || [];

            allDepartments =
                departmentsResult.data || [];

            rolesMap = new Map(
                allRoles.map((r) => [
                    Number(r.id),
                    r.name
                ])
            );

            departmentsMap = new Map(
                allDepartments.map((d) => [
                    Number(d.id),
                    d.name
                ])
            );

            populateFilterDropdowns();
            populateModalDropdowns();

            loading?.classList.add(
                'd-none'
            );

            renderUsers(
                allUsers
            );

        } catch (err) {

            loading?.classList.add(
                'd-none'
            );

            tableWrapper?.classList.add(
                'd-none'
            );

            empty?.classList.add(
                'd-none'
            );

            error?.classList.remove(
                'd-none'
            );
        }
    }


    /* ==========================================================================
       DROPDOWN POPULATION
       ========================================================================== */

    function populateFilterDropdowns() {

        const roleFilter =
            document.getElementById(
                'userRoleFilter'
            );

        const departmentFilter =
            document.getElementById(
                'userDepartmentFilter'
            );

        if (roleFilter) {

            const currentRole =
                roleFilter.value;

            roleFilter.innerHTML =
                '<option value="">All Roles</option>';

            allRoles.forEach((role) => {

                const opt =
                    document.createElement(
                        'option'
                    );

                opt.value =
                    String(role.id);

                opt.textContent =
                    role.name;

                roleFilter.appendChild(
                    opt
                );
            });

            if (currentRole) {
                roleFilter.value =
                    currentRole;
            }
        }

        if (departmentFilter) {

            const currentDept =
                departmentFilter.value;

            departmentFilter.innerHTML =
                '<option value="">All Departments</option>';

            allDepartments.forEach((dept) => {

                const opt =
                    document.createElement(
                        'option'
                    );

                opt.value =
                    String(dept.id);

                opt.textContent =
                    dept.name;

                departmentFilter.appendChild(
                    opt
                );
            });

            if (currentDept) {
                departmentFilter.value =
                    currentDept;
            }
        }
    }


    function populateModalDropdowns() {

        const departmentSelect =
            document.getElementById(
                'userDepartment'
            );

        const roleSelect =
            document.getElementById(
                'userRole'
            );

        if (departmentSelect) {

            departmentSelect.innerHTML =
                '<option value="">Select Department...</option>';

            allDepartments.forEach((dept) => {

                const opt =
                    document.createElement(
                        'option'
                    );

                opt.value =
                    String(dept.id);

                opt.textContent =
                    dept.name;

                departmentSelect.appendChild(
                    opt
                );
            });
        }

        if (roleSelect) {

            roleSelect.innerHTML =
                '<option value="">Select Role...</option>';

            allRoles.forEach((role) => {

                const opt =
                    document.createElement(
                        'option'
                    );

                opt.value =
                    String(role.id);

                opt.textContent =
                    role.name;

                roleSelect.appendChild(
                    opt
                );
            });
        }
    }


    /* ==========================================================================
       RENDERING
       ========================================================================== */

    function renderUsers(users) {

        const tableBody =
            document.getElementById(
                'usersTableBody'
            );

        const empty =
            document.getElementById(
                'usersEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'usersTableWrapper'
            );

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = '';

        if (!users.length) {

            tableWrapper?.classList.add(
                'd-none'
            );

            empty?.classList.remove(
                'd-none'
            );

            return;
        }

        empty?.classList.add(
            'd-none'
        );

        tableWrapper?.classList.remove(
            'd-none'
        );

        users.forEach((user) => {

            const row =
                document.createElement(
                    'tr'
                );

            const fullName =
                `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
                'Unnamed User';

            const departmentName =
                user.department_id &&
                departmentsMap.has(
                    Number(user.department_id)
                )
                    ? departmentsMap.get(
                        Number(user.department_id)
                    )
                    : 'Unassigned';

            const roleName =
                user.role_id &&
                rolesMap.has(
                    Number(user.role_id)
                )
                    ? rolesMap.get(
                        Number(user.role_id)
                    )
                    : 'Unassigned';

            const hasRole =
                roleName !==
                'Unassigned';

            const isActive =
                String(
                    user.is_active
                ) === '1';

            const statusLabel =
                isActive
                    ? 'Active'
                    : 'Inactive';

            const statusClass =
                isActive
                    ? 'active'
                    : 'inactive';

            row.innerHTML = `

                <td>

                    <div class="booking-room-name">
                        ${escapeHtml(
                            fullName
                        )}
                    </div>

                </td>

                <td>

                    <span class="booking-description">
                        ${escapeHtml(
                            user.email ||
                            '—'
                        )}
                    </span>

                </td>

                <td>

                    <span class="booking-description">
                        ${escapeHtml(
                            user.phone ||
                            '—'
                        )}
                    </span>

                </td>

                <td>

                    <span class="booking-description">
                        ${escapeHtml(
                            departmentName
                        )}
                    </span>

                </td>

                <td>

                    <span class="user-role-badge ${
                        hasRole
                            ? ''
                            : 'user-role-unassigned'
                    }">
                        ${escapeHtml(
                            roleName
                        )}
                    </span>

                </td>

                <td>

                    <span class="booking-status booking-status-${statusClass}">
                        ${statusLabel}
                    </span>

                </td>

                <td>

                    <div class="user-actions">

                        <button
                            type="button"
                            class="user-action-btn user-edit-btn"
                            data-user-id="${escapeHtml(
                                user.id
                            )}"
                            title="Edit user"
                            aria-label="Edit user"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button
                            type="button"
                            class="user-action-btn user-delete-btn"
                            data-user-id="${escapeHtml(
                                user.id
                            )}"
                            title="Delete user"
                            aria-label="Delete user"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>

                </td>

            `;

            tableBody.appendChild(
                row
            );
        });

        setupUserActionButtons();
    }


    /* ==========================================================================
       FILTERS
       ========================================================================== */

    function setupUserFilters() {

        const searchInput =
            document.getElementById(
                'userSearch'
            );

        const roleFilter =
            document.getElementById(
                'userRoleFilter'
            );

        const departmentFilter =
            document.getElementById(
                'userDepartmentFilter'
            );

        const statusFilter =
            document.getElementById(
                'userStatusFilter'
            );

        searchInput?.addEventListener(
            'input',
            applyUserFilters
        );

        roleFilter?.addEventListener(
            'change',
            applyUserFilters
        );

        departmentFilter?.addEventListener(
            'change',
            applyUserFilters
        );

        statusFilter?.addEventListener(
            'change',
            applyUserFilters
        );
    }


    function applyUserFilters() {

        const searchInput =
            document.getElementById(
                'userSearch'
            );

        const roleFilter =
            document.getElementById(
                'userRoleFilter'
            );

        const departmentFilter =
            document.getElementById(
                'userDepartmentFilter'
            );

        const statusFilter =
            document.getElementById(
                'userStatusFilter'
            );

        const searchTerm =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const selectedRole =
            roleFilter
                ? roleFilter.value
                : '';

        const selectedDepartment =
            departmentFilter
                ? departmentFilter.value
                : '';

        const selectedStatus =
            statusFilter
                ? statusFilter.value
                : '';

        const filteredUsers =
            allUsers.filter((user) => {

                const fullName =
                    `${user.first_name || ''} ${user.last_name || ''}`.toLowerCase();

                const email =
                    String(
                        user.email || ''
                    ).toLowerCase();

                const phone =
                    String(
                        user.phone || ''
                    ).toLowerCase();

                const matchesSearch =
                    !searchTerm ||
                    fullName.includes(
                        searchTerm
                    ) ||
                    email.includes(
                        searchTerm
                    ) ||
                    phone.includes(
                        searchTerm
                    );

                const matchesRole =
                    !selectedRole ||
                    String(
                        user.role_id || ''
                    ) === selectedRole;

                const matchesDepartment =
                    !selectedDepartment ||
                    String(
                        user.department_id || ''
                    ) === selectedDepartment;

                const matchesStatus =
                    !selectedStatus ||
                    String(
                        user.is_active
                    ) === selectedStatus;

                return (
                    matchesSearch &&
                    matchesRole &&
                    matchesDepartment &&
                    matchesStatus
                );
            });

        renderUsers(
            filteredUsers
        );
    }


    /* ==========================================================================
       MODAL
       ========================================================================== */

    function setupUserModal() {

        const modal =
            document.getElementById(
                'userModal'
            );

        const newUserBtn =
            document.getElementById(
                'newUserBtn'
            );

        const closeUserModal =
            document.getElementById(
                'closeUserModal'
            );

        const cancelUserBtn =
            document.getElementById(
                'cancelUserBtn'
            );

        const form =
            document.getElementById(
                'userForm'
            );

        if (
            !modal ||
            !newUserBtn ||
            !form
        ) {
            return;
        }

        newUserBtn.addEventListener(
            'click',
            () => {

                resetUserForm();

                openUserModal();
            }
        );

        closeUserModal?.addEventListener(
            'click',
            closeUserModalWindow
        );

        cancelUserBtn?.addEventListener(
            'click',
            closeUserModalWindow
        );

        modal.addEventListener(
            'click',
            (event) => {

                if (
                    event.target === modal
                ) {
                    closeUserModalWindow();
                }
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {

                if (
                    event.key === 'Escape' &&
                    !modal.classList.contains(
                        'd-none'
                    )
                ) {
                    closeUserModalWindow();
                }
            }
        );

        form.addEventListener(
            'submit',
            handleUserFormSubmit
        );
    }


    function openUserModal() {

        const modal =
            document.getElementById(
                'userModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.remove(
            'd-none'
        );

        document.body.classList.add(
            'booking-modal-open'
        );

        setTimeout(() => {

            document.getElementById(
                'userFirstName'
            )?.focus();

        }, 50);
    }


    function closeUserModalWindow() {

        const modal =
            document.getElementById(
                'userModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.add(
            'd-none'
        );

        document.body.classList.remove(
            'booking-modal-open'
        );
    }


    function resetUserForm() {

        const form =
            document.getElementById(
                'userForm'
            );

        form?.reset();

        const userId =
            document.getElementById(
                'userId'
            );

        if (userId) {
            userId.value = '';
        }

        const passwordInput =
            document.getElementById(
                'userPassword'
            );

        const passwordAsterisk =
            document.getElementById(
                'passwordRequiredAsterisk'
            );

        const passwordHint =
            document.getElementById(
                'userPasswordHint'
            );

        if (passwordInput) {
            passwordInput.required =
                true;
            passwordInput.value =
                '';
        }

        passwordAsterisk?.classList.remove(
            'd-none'
        );

        passwordHint?.classList.add(
            'd-none'
        );

        const modalTitle =
            document.getElementById(
                'userModalTitle'
            );

        if (modalTitle) {

            modalTitle.textContent =
                'New User';
        }

        const modalSubtitle =
            document.getElementById(
                'userModalSubtitle'
            );

        if (modalSubtitle) {

            modalSubtitle.textContent =
                'Add a new user account to the system.';
        }

        const submitButton =
            document.getElementById(
                'submitUserBtn'
            );

        if (submitButton) {

            submitButton.disabled =
                false;

            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Save User
            `;
        }

        hideUserFormMessages();
    }


    /* ==========================================================================
       ACTION BUTTONS
       ========================================================================== */

    function setupUserActionButtons() {

        const editButtons =
            document.querySelectorAll(
                '.user-edit-btn'
            );

        const deleteButtons =
            document.querySelectorAll(
                '.user-delete-btn'
            );

        editButtons.forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const userId =
                        button.dataset.userId;

                    editUser(
                        userId
                    );
                }
            );
        });

        deleteButtons.forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const userId =
                        button.dataset.userId;

                    deleteUser(
                        userId
                    );
                }
            );
        });
    }


    function findUserById(userId) {

        return allUsers.find(
            (user) =>
                String(user.id) ===
                String(userId)
        );
    }


    /* ==========================================================================
       EDIT USER
       ========================================================================== */

    function editUser(userId) {

        const user =
            findUserById(
                userId
            );

        if (!user) {

            showAppNotification(
                'Unable to find the selected user.',
                'error',
                'User Not Found'
            );

            return;
        }

        const userIdInput =
            document.getElementById(
                'userId'
            );

        const firstNameInput =
            document.getElementById(
                'userFirstName'
            );

        const lastNameInput =
            document.getElementById(
                'userLastName'
            );

        const emailInput =
            document.getElementById(
                'userEmail'
            );

        const passwordInput =
            document.getElementById(
                'userPassword'
            );

        const passwordAsterisk =
            document.getElementById(
                'passwordRequiredAsterisk'
            );

        const passwordHint =
            document.getElementById(
                'userPasswordHint'
            );

        const departmentInput =
            document.getElementById(
                'userDepartment'
            );

        const roleInput =
            document.getElementById(
                'userRole'
            );

        const phoneInput =
            document.getElementById(
                'userPhone'
            );

        const statusInput =
            document.getElementById(
                'userStatus'
            );

        if (userIdInput) {
            userIdInput.value =
                user.id;
        }

        if (firstNameInput) {
            firstNameInput.value =
                user.first_name || '';
        }

        if (lastNameInput) {
            lastNameInput.value =
                user.last_name || '';
        }

        if (emailInput) {
            emailInput.value =
                user.email || '';
        }

        if (passwordInput) {
            passwordInput.required =
                false;
            passwordInput.value =
                '';
        }

        passwordAsterisk?.classList.add(
            'd-none'
        );

        passwordHint?.classList.remove(
            'd-none'
        );

        if (departmentInput) {
            departmentInput.value =
                user.department_id
                    ? String(user.department_id)
                    : '';
        }

        if (roleInput) {
            roleInput.value =
                user.role_id
                    ? String(user.role_id)
                    : '';
        }

        if (phoneInput) {
            phoneInput.value =
                user.phone || '';
        }

        if (statusInput) {
            statusInput.value =
                String(
                    user.is_active ?? '1'
                );
        }

        const modalTitle =
            document.getElementById(
                'userModalTitle'
            );

        if (modalTitle) {

            modalTitle.textContent =
                'Edit User';
        }

        const modalSubtitle =
            document.getElementById(
                'userModalSubtitle'
            );

        if (modalSubtitle) {

            modalSubtitle.textContent =
                'Update user profile and account details.';
        }

        const submitButton =
            document.getElementById(
                'submitUserBtn'
            );

        if (submitButton) {

            submitButton.disabled =
                false;

            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Save Changes
            `;
        }

        hideUserFormMessages();

        openUserModal();
    }


    /* ==========================================================================
       CREATE / UPDATE USER
       ========================================================================== */

    async function handleUserFormSubmit(
        event
    ) {

        event.preventDefault();

        const form =
            document.getElementById(
                'userForm'
            );

        const submitButton =
            document.getElementById(
                'submitUserBtn'
            );

        if (
            !form ||
            !submitButton
        ) {
            return;
        }

        hideUserFormMessages();

        const userId =
            document.getElementById(
                'userId'
            ).value.trim();

        const firstName =
            document.getElementById(
                'userFirstName'
            ).value.trim();

        const lastName =
            document.getElementById(
                'userLastName'
            ).value.trim();

        const email =
            document.getElementById(
                'userEmail'
            ).value.trim();

        const password =
            document.getElementById(
                'userPassword'
            ).value;

        const departmentId =
            document.getElementById(
                'userDepartment'
            ).value;

        const roleId =
            document.getElementById(
                'userRole'
            ).value;

        const phone =
            document.getElementById(
                'userPhone'
            ).value.trim();

        const isActive =
            document.getElementById(
                'userStatus'
            ).value;

        const isEditing =
            Boolean(userId);

        if (!firstName) {

            showUserFormError(
                'Please enter a first name.'
            );

            return;
        }

        if (!lastName) {

            showUserFormError(
                'Please enter a last name.'
            );

            return;
        }

        if (!email) {

            showUserFormError(
                'Please enter an email address.'
            );

            return;
        }

        if (!isEditing && !password) {

            showUserFormError(
                'Password is required when creating a new user.'
            );

            return;
        }

        if (password && password.length < 6) {

            showUserFormError(
                'Password must be at least 6 characters long.'
            );

            return;
        }

        const payload = {
            first_name: firstName,
            last_name: lastName,
            email: email,
            department_id: departmentId ? Number(departmentId) : null,
            role_id: roleId ? Number(roleId) : null,
            phone: phone || null,
            is_active: Number(isActive)
        };

        if (password) {
            payload.password = password;
        }

        const url =
            isEditing
                ? `/api/users/${userId}`
                : '/api/users';

        const method =
            isEditing
                ? 'PUT'
                : 'POST';

        try {

            submitButton.disabled =
                true;

            submitButton.innerHTML = `
                <i class="bi bi-arrow-repeat spin"></i>
                ${
                    isEditing
                        ? 'Updating...'
                        : 'Creating...'
                }
            `;

            const response =
                await fetch(
                    url,
                    {
                        method:
                            method,

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json'
                        },

                        body:
                            JSON.stringify(
                                payload
                            )
                    }
                );

            const result =
                await response.json();

            if (
                !response.ok ||
                result.status !==
                'success'
            ) {

                handleUserApiError(
                    response.status,
                    result
                );

                return;
            }

            const successMessage =
                result.message ||
                (
                    isEditing
                        ? 'User updated successfully.'
                        : 'User created successfully.'
                );

            showUserFormSuccess(
                successMessage
            );

            showAppNotification(
                successMessage,
                'success',
                isEditing
                    ? 'User Updated'
                    : 'User Created'
            );

            await loadUsersData();

            setTimeout(() => {

                closeUserModalWindow();

            }, 800);

        } catch (error) {

            showUserFormError(
                'Unable to save user. Please try again.'
            );

            showAppNotification(
                'Unable to save user. Please try again.',
                'error',
                'User Save Failed'
            );

        } finally {

            submitButton.disabled =
                false;

            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                ${
                    isEditing
                        ? 'Save Changes'
                        : 'Save User'
                }
            `;
        }
    }


    /* ==========================================================================
       DELETE USER
       ========================================================================== */

    async function deleteUser(userId) {

        const user =
            findUserById(
                userId
            );

        if (!user) {

            showAppNotification(
                'Unable to find the selected user.',
                'error',
                'User Not Found'
            );

            return;
        }

        const fullName =
            `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
            'this user';

        showAppConfirm(

            `Are you sure you want to delete "${fullName}" (${user.email})?`,

            async () => {

                await performUserDelete(
                    userId,
                    fullName
                );
            },

            'Delete User',

            'Delete User'
        );
    }


    async function performUserDelete(
        userId,
        userName
    ) {

        try {

            const response =
                await fetch(
                    `/api/users/${userId}`,
                    {
                        method:
                            'DELETE',

                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                );

            const result =
                await response.json();

            if (
                !response.ok ||
                result.status !==
                'success'
            ) {

                if (response.status === 409) {

                    showAppNotification(
                        result.message ||
                        'This user cannot be deleted because they are associated with existing records.',
                        'warning',
                        'Cannot Delete User'
                    );

                    return;
                }

                throw new Error(
                    result.message ||
                    'Unable to delete user.'
                );
            }

            await loadUsersData();

            showAppNotification(

                result.message ||
                `"${userName}" has been deleted successfully.`,

                'success',

                'User Deleted'
            );

        } catch (error) {

            showAppNotification(

                error.message ||
                'Unable to delete user. Please try again.',

                'error',

                'Delete Failed'
            );
        }
    }


    /* ==========================================================================
       API ERROR HANDLING
       ========================================================================== */

    function handleUserApiError(
        status,
        result
    ) {

        if (
            status === 422 &&
            result.errors
        ) {

            const messages =
                Object.values(
                    result.errors
                );

            const message =
                messages.join(' ');

            showUserFormError(
                message
            );

            showAppNotification(
                message,
                'error',
                'Validation Error'
            );

            return;
        }

        if (
            status === 422 &&
            result.message
        ) {

            showUserFormError(
                result.message
            );

            showAppNotification(
                result.message,
                'error',
                'Validation Error'
            );

            return;
        }

        if (
            status === 404
        ) {

            const message =
                result.message ||
                'User could not be found.';

            showUserFormError(
                message
            );

            showAppNotification(
                message,
                'error',
                'User Not Found'
            );

            return;
        }

        if (
            status === 409
        ) {

            const message =
                result.message ||
                'A user with this email address already exists.';

            showUserFormError(
                message
            );

            showAppNotification(
                message,
                'warning',
                'Duplicate User'
            );

            return;
        }

        const message =
            result.message ||
            'Unable to save user.';

        showUserFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'User Error'
        );
    }


    /* ==========================================================================
       FORM MESSAGES
       ========================================================================== */

    function showUserFormError(
        message
    ) {

        const errorBox =
            document.getElementById(
                'userFormError'
            );

        const errorText =
            document.getElementById(
                'userFormErrorText'
            );

        const successBox =
            document.getElementById(
                'userFormSuccess'
            );

        successBox?.classList.add(
            'd-none'
        );

        if (errorText) {

            errorText.textContent =
                message;
        }

        errorBox?.classList.remove(
            'd-none'
        );
    }


    function showUserFormSuccess(
        message
    ) {

        const successBox =
            document.getElementById(
                'userFormSuccess'
            );

        const successText =
            document.getElementById(
                'userFormSuccessText'
            );

        const errorBox =
            document.getElementById(
                'userFormError'
            );

        errorBox?.classList.add(
            'd-none'
        );

        if (successText) {

            successText.textContent =
                message;
        }

        successBox?.classList.remove(
            'd-none'
        );
    }


    function hideUserFormMessages() {

        document.getElementById(
            'userFormError'
        )?.classList.add(
            'd-none'
        );

        document.getElementById(
            'userFormSuccess'
        )?.classList.add(
            'd-none'
        );
    }

})();
