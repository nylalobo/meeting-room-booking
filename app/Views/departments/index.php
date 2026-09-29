<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Departments</h1>
        <p class="page-subtitle">Manage organizational departments, divisions, and team structures.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newDepartmentBtn">
        <i class="bi bi-plus-lg"></i>
        New Department
    </button>
</div>

<div class="booking-toolbar departments-toolbar">
    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="departmentSearch"
            placeholder="Search departments..."
            aria-label="Search departments"
        >
    </div>
</div>

<div class="panel-card departments-panel">

    <div id="departmentsLoading" class="bookings-state">
        <div class="meetspace-loader" role="status" aria-live="polite">
            <div class="meetspace-loader-track">
                <div class="meetspace-loader-bar">
                    <div class="meetspace-loader-highlights"></div>
                </div>
            </div>
            <div class="meetspace-loader-text">
                <span class="loader-label">Loading departments</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <div id="departmentsError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load departments.</span>
    </div>

    <div id="departmentsEmpty" class="bookings-state d-none">
        <i class="bi bi-diagram-3"></i>
        <span>No departments found.</span>
    </div>

    <div id="departmentsTableWrapper" class="table-responsive d-none">
        <table class="table custom-dark-table departments-table align-middle mb-0">
            <thead>
                <tr>
                    <th>DEPARTMENT</th>
                    <th>DESCRIPTION</th>
                    <th>CREATED</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="departmentsTableBody"></tbody>
        </table>
    </div>

    <div id="departmentsPagination" class="meetspace-pagination-wrapper d-none"></div>

</div>


<!-- ================================================================
     Department Modal (Create / Edit)
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="departmentModal">

    <div class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="departmentModalTitle">

        <div class="booking-modal-header">
            <div>
                <h2 id="departmentModalTitle">New Department</h2>
                <p id="departmentModalSubtitle">Create a new organizational department.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeDepartmentModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="departmentForm">

            <input type="hidden" id="departmentId">

            <div id="departmentFormError" class="booking-form-alert booking-form-error d-none" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle"></i>
                <span id="departmentFormErrorText"></span>
            </div>

            <div id="departmentFormSuccess" class="booking-form-alert booking-form-success d-none" role="status" aria-live="polite">
                <i class="bi bi-check-circle"></i>
                <span id="departmentFormSuccessText"></span>
            </div>

            <div class="booking-form-grid">

                <!-- Department Name -->
                <div class="booking-form-group booking-form-full">
                    <label for="departmentName">
                        Department Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="departmentName"
                        name="name"
                        maxlength="100"
                        placeholder="e.g. Human Resources"
                        required
                    >
                </div>

                <!-- Description -->
                <div class="booking-form-group booking-form-full">
                    <label for="departmentDescription">
                        Description
                    </label>

                    <textarea
                        id="departmentDescription"
                        name="description"
                        rows="3"
                        placeholder="Add department details or description..."
                    ></textarea>
                </div>

            </div>

            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelDepartmentBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitDepartmentBtn"
                >
                    <i class="bi bi-diagram-3"></i>
                    Create Department
                </button>

            </div>

        </form>

    </div>

</div>

<style>
.departments-panel {
    padding: 0;
    overflow: hidden;
}

.departments-table thead th {
    padding: 15px 18px;
}

.departments-table tbody td {
    padding: 16px 18px;
}

.department-name {
    font-weight: 600;
    color: #f1f5f9;
    font-size: 14px;
}

.department-description {
    color: #94a3b8;
    font-size: 13px;
    max-width: 450px;
    line-height: 1.4;
}

.department-date {
    color: #8496b5;
    font-size: 13px;
}

.department-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.department-action-btn {
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

.department-action-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}

.department-delete-btn:hover {
    background-color: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}
</style>

<?= $this->endSection() ?>

