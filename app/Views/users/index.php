<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle">Manage system users, department assignments, and organizational roles.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newUserBtn">
        <i class="bi bi-person-plus"></i>
        New User
    </button>
</div>

<div class="booking-toolbar users-toolbar">

    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="userSearch"
            placeholder="Search users, emails, or phones..."
            aria-label="Search users"
        >
    </div>

    <select id="userRoleFilter" class="booking-filter" aria-label="Filter by role">
        <option value="">All Roles</option>
    </select>

    <select id="userDepartmentFilter" class="booking-filter" aria-label="Filter by department">
        <option value="">All Departments</option>
    </select>

    <select id="userStatusFilter" class="booking-filter" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>

</div>

<div class="panel-card users-panel">

    <div id="usersLoading" class="bookings-state">
        <i class="bi bi-arrow-repeat spin"></i>
        Loading users...
    </div>

    <div id="usersError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load users.</span>
    </div>

    <div id="usersEmpty" class="bookings-state d-none">
        <i class="bi bi-people"></i>
        <span>No users found.</span>
    </div>

    <div id="usersTableWrapper" class="table-responsive d-none">

        <table class="table custom-dark-table users-table align-middle mb-0">

            <thead>
                <tr>
                    <th>USER</th>
                    <th>EMAIL</th>
                    <th>PHONE</th>
                    <th>DEPARTMENT</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="usersTableBody"></tbody>

        </table>

    </div>

</div>


<!-- ================================================================
     User Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="userModal">

    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="userModalTitle"
    >

        <div class="booking-modal-header">

            <div>
                <h2 id="userModalTitle">New User</h2>
                <p id="userModalSubtitle">Add a new user account to the system.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeUserModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form id="userForm">

            <input type="hidden" id="userId">

            <div id="userFormError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="userFormErrorText"></span>
            </div>

            <div id="userFormSuccess" class="booking-form-alert booking-form-success d-none">
                <i class="bi bi-check-circle"></i>
                <span id="userFormSuccessText"></span>
            </div>


            <div class="booking-form-grid">

                <div class="booking-form-group">
                    <label for="userFirstName">
                        First Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="userFirstName"
                        maxlength="100"
                        placeholder="e.g. Nyla"
                        required
                    >
                </div>


                <div class="booking-form-group">
                    <label for="userLastName">
                        Last Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="userLastName"
                        maxlength="100"
                        placeholder="e.g. Lobo"
                        required
                    >
                </div>


                <div class="booking-form-group booking-form-full">
                    <label for="userEmail">
                        Email Address
                        <span>*</span>
                    </label>

                    <input
                        type="email"
                        id="userEmail"
                        maxlength="255"
                        placeholder="e.g. user@example.com"
                        required
                    >
                </div>


                <div class="booking-form-group booking-form-full">
                    <label for="userPassword">
                        Password
                        <span id="passwordRequiredAsterisk">*</span>
                    </label>

                    <input
                        type="password"
                        id="userPassword"
                        minlength="6"
                        placeholder="Enter account password (min. 6 characters)"
                    >

                    <small class="user-password-hint d-none" id="userPasswordHint">
                        Leave blank to keep the current password.
                    </small>
                </div>


                <div class="booking-form-group">
                    <label for="userDepartment">
                        Department
                    </label>

                    <select id="userDepartment">
                        <option value="">Select Department...</option>
                    </select>
                </div>


                <div class="booking-form-group">
                    <label for="userRole">
                        Role
                    </label>

                    <select id="userRole">
                        <option value="">Select Role...</option>
                    </select>
                </div>


                <div class="booking-form-group">
                    <label for="userPhone">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="userPhone"
                        maxlength="30"
                        placeholder="e.g. +91 9876543210"
                    >
                </div>


                <div class="booking-form-group">
                    <label for="userStatus">
                        Status
                        <span>*</span>
                    </label>

                    <select id="userStatus" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

            </div>


            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelUserBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitUserBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Save User
                </button>

            </div>

        </form>

    </div>

</div>

<style>
.users-panel {
    padding: 0;
    overflow: hidden;
}

.users-table thead th {
    padding: 15px 18px;
}

.users-table tbody td {
    padding: 16px 18px;
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: transparent;
    color: #8496b5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.15s ease;
}

.user-action-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}

.user-delete-btn:hover {
    background-color: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.user-role-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 600;
    background-color: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.user-role-unassigned {
    background-color: rgba(148, 163, 184, 0.12);
    color: #94a3b8;
    border-color: rgba(148, 163, 184, 0.2);
}

.booking-status-active {
    background-color: rgba(45, 212, 191, 0.12);
    color: var(--color-teal);
    border: 1px solid rgba(45, 212, 191, 0.25);
}

.booking-status-inactive {
    background-color: rgba(148, 163, 184, 0.12);
    color: #94a3b8;
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.user-password-hint {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #94a3b8;
}
</style>

<?= $this->endSection() ?>
