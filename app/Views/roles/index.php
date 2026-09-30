<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">User Roles</h1>
        <p class="page-subtitle">Manage organizational roles, system access levels, and user assignments.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newRoleBtn">
        <i class="bi bi-plus-lg"></i>
        New Role
    </button>
</div>

<div class="booking-toolbar roles-toolbar">
    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="roleSearch"
            placeholder="Search roles..."
            aria-label="Search roles"
        >
    </div>
</div>

<div class="panel-card roles-panel">

    <div id="rolesLoading" class="bookings-state">
        <div class="meetspace-loader" role="status" aria-live="polite">
            <div class="meetspace-loader-track">
                <div class="meetspace-loader-bar">
                    <div class="meetspace-loader-highlights"></div>
                </div>
            </div>
            <div class="meetspace-loader-text">
                <span class="loader-label">Loading user roles</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <div id="rolesError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load roles.</span>
    </div>

    <div id="rolesEmpty" class="bookings-state d-none">
        <i class="bi bi-shield-lock"></i>
        <span>No roles found.</span>
    </div>

    <div id="rolesTableWrapper" class="table-responsive d-none">
        <table class="table custom-dark-table roles-table align-middle mb-0">
            <thead>
                <tr>
                    <th>ROLE NAME</th>
                    <th>DESCRIPTION</th>
                    <th>ASSIGNED USERS</th>
                    <th>CREATED</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="rolesTableBody"></tbody>
        </table>
    </div>

</div>


<!-- ================================================================
     Role Modal (Create / Edit)
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="roleModal">

    <div class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="roleModalTitle">

        <div class="booking-modal-header">
            <div>
                <h2 id="roleModalTitle">New Role</h2>
                <p id="roleModalSubtitle">Define a new organizational user role.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeRoleModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="roleForm" novalidate>

            <input type="hidden" id="roleId">

            <div id="roleFormError" class="booking-form-alert booking-form-error d-none" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle"></i>
                <span id="roleFormErrorText"></span>
            </div>

            <div class="booking-form-group">
                <label for="roleName" class="booking-form-label">
                    Role Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="roleName"
                    class="booking-form-control"
                    placeholder="e.g. Operations Coordinator"
                    required
                    maxlength="50"
                    autocomplete="off"
                >
                <div id="roleNameFeedback" class="invalid-feedback d-none text-danger mt-1" style="font-size: 12px;"></div>
            </div>

            <div class="booking-form-group">
                <label for="roleDescription" class="booking-form-label">
                    Description <span class="label-optional" style="color: #64748b; font-size: 11.5px;">(Optional)</span>
                </label>
                <textarea
                    id="roleDescription"
                    class="booking-form-control"
                    rows="3"
                    placeholder="Describe the permissions or responsibilities for this role..."
                    maxlength="255"
                ></textarea>
            </div>

            <div class="booking-modal-actions">
                <button
                    type="button"
                    class="btn-secondary-action"
                    id="cancelRoleBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-primary-action"
                    id="saveRoleBtn"
                >
                    <span id="saveRoleBtnText">Save Role</span>
                    <span id="saveRoleBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>

        </form>

    </div>

</div>

<?= $this->endSection() ?>
