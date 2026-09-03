<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Facilities</h1>
        <p class="page-subtitle">Manage meeting room equipment, amenities, and office resources.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newFacilityBtn">
        <i class="bi bi-plus-lg"></i>
        New Facility
    </button>
</div>

<div class="booking-toolbar facilities-toolbar">

    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="facilitySearch"
            placeholder="Search facilities..."
            aria-label="Search facilities"
        >
    </div>

    <select id="facilityStatusFilter" class="booking-filter" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>

</div>

<div class="panel-card facilities-panel">

    <div id="facilitiesLoading" class="bookings-state">
        <i class="bi bi-arrow-repeat spin"></i>
        Loading facilities...
    </div>

    <div id="facilitiesError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load facilities.</span>
    </div>

    <div id="facilitiesEmpty" class="bookings-state d-none">
        <i class="bi bi-building-gear"></i>
        <span>No facilities found.</span>
    </div>

    <div id="facilitiesTableWrapper" class="table-responsive d-none">

        <table class="table custom-dark-table facilities-table align-middle mb-0">

            <thead>
                <tr>
                    <th>FACILITY</th>
                    <th>DESCRIPTION</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="facilitiesTableBody"></tbody>

        </table>

    </div>

</div>


<!-- ================================================================
     Facility Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="facilityModal">

    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="facilityModalTitle"
    >

        <div class="booking-modal-header">

            <div>
                <h2 id="facilityModalTitle">New Facility</h2>
                <p id="facilityModalSubtitle">Create a new meeting room facility or equipment item.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeFacilityModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form id="facilityForm">

            <input type="hidden" id="facilityId">

            <div id="facilityFormError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="facilityFormErrorText"></span>
            </div>

            <div id="facilityFormSuccess" class="booking-form-alert booking-form-success d-none">
                <i class="bi bi-check-circle"></i>
                <span id="facilityFormSuccessText"></span>
            </div>


            <div class="booking-form-grid">

                <div class="booking-form-group booking-form-full">
                    <label for="facilityName">
                        Facility Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="facilityName"
                        maxlength="100"
                        placeholder="e.g. 4K Video Projector"
                        required
                    >
                </div>


                <div class="booking-form-group booking-form-full">
                    <label for="facilityDescription">
                        Description
                    </label>

                    <textarea
                        id="facilityDescription"
                        rows="3"
                        placeholder="Describe the facility or equipment capabilities..."
                    ></textarea>
                </div>


                <div class="booking-form-group">
                    <label for="facilityStatus">
                        Status
                        <span>*</span>
                    </label>

                    <select id="facilityStatus" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

            </div>


            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelFacilityBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitFacilityBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Save Facility
                </button>

            </div>

        </form>

    </div>

</div>

<style>
.facilities-panel {
    padding: 0;
    overflow: hidden;
}

.facilities-table thead th {
    padding: 15px 18px;
}

.facilities-table tbody td {
    padding: 16px 18px;
}

.facility-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.facility-action-btn {
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

.facility-action-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}

.facility-delete-btn:hover {
    background-color: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
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
</style>

<?= $this->endSection() ?>
