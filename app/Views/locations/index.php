<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Locations</h1>
        <p class="page-subtitle">Manage office locations, buildings, and campuses.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newLocationBtn">
        <i class="bi bi-plus-lg"></i>
        New Location
    </button>
</div>

<div class="booking-toolbar locations-toolbar">

    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="locationSearch"
            placeholder="Search locations..."
            aria-label="Search locations"
        >
    </div>

    <select id="locationStatusFilter" class="booking-filter" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>

</div>

<div class="panel-card locations-panel">

    <div id="locationsLoading" class="bookings-state">
        <div class="meetspace-loader" role="status" aria-live="polite">
            <div class="meetspace-loader-track">
                <div class="meetspace-loader-bar">
                    <div class="meetspace-loader-highlights"></div>
                </div>
            </div>
            <div class="meetspace-loader-text">
                <span class="loader-label">Loading locations</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <div id="locationsError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load locations.</span>
    </div>

    <div id="locationsEmpty" class="bookings-state d-none">
        <i class="bi bi-geo-alt"></i>
        <span>No locations found.</span>
    </div>

    <div id="locationsTableWrapper" class="table-responsive d-none">

        <table class="table custom-dark-table locations-table align-middle mb-0">

            <thead>
                <tr>
                    <th>LOCATION</th>
                    <th>ADDRESS</th>
                    <th>CITY</th>
                    <th>STATE</th>
                    <th>COUNTRY</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="locationsTableBody"></tbody>

        </table>

    </div>

</div>


<!-- ================================================================
     Location Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="locationModal">

    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="locationModalTitle"
    >

        <div class="booking-modal-header">

            <div>
                <h2 id="locationModalTitle">New Location</h2>
                <p id="locationModalSubtitle">Create a new office location.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeLocationModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form id="locationForm">

            <input type="hidden" id="locationId">

            <div id="locationFormError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="locationFormErrorText"></span>
            </div>

            <div id="locationFormSuccess" class="booking-form-alert booking-form-success d-none">
                <i class="bi bi-check-circle"></i>
                <span id="locationFormSuccessText"></span>
            </div>


            <div class="booking-form-grid">

                <div class="booking-form-group booking-form-full">
                    <label for="locationName">
                        Location Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="locationName"
                        maxlength="150"
                        placeholder="e.g. Headquarters Campus"
                        required
                    >
                </div>


                <div class="booking-form-group booking-form-full">
                    <label for="locationAddress">
                        Address
                    </label>

                    <input
                        type="text"
                        id="locationAddress"
                        maxlength="255"
                        placeholder="e.g. 123 Tech Boulevard, Suite 400"
                    >
                </div>


                <div class="booking-form-group">
                    <label for="locationCity">
                        City
                    </label>

                    <input
                        type="text"
                        id="locationCity"
                        maxlength="100"
                        placeholder="e.g. Bengaluru"
                    >
                </div>


                <div class="booking-form-group">
                    <label for="locationState">
                        State
                    </label>

                    <input
                        type="text"
                        id="locationState"
                        maxlength="100"
                        placeholder="e.g. Karnataka"
                    >
                </div>


                <div class="booking-form-group">
                    <label for="locationCountry">
                        Country
                    </label>

                    <input
                        type="text"
                        id="locationCountry"
                        maxlength="100"
                        placeholder="e.g. India"
                    >
                </div>


                <div class="booking-form-group">
                    <label for="locationStatus">
                        Status
                    </label>

                    <select id="locationStatus">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

            </div>


            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelLocationBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitLocationBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Save Location
                </button>

            </div>

        </form>

    </div>

</div>

<style>
.locations-panel {
    padding: 0;
    overflow: hidden;
}

.locations-table thead th {
    padding: 15px 18px;
}

.locations-table tbody td {
    padding: 16px 18px;
}

.location-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.location-action-btn {
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

.location-action-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}

.location-delete-btn:hover {
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

