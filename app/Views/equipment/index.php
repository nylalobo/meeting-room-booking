<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isLoggedIn = session()->get('isLoggedIn') === true && !empty(session()->get('user_id'));
$roleName   = (string) (session()->get('role_name') ?? '');
$roleId     = (int) (session()->get('role_id') ?? 0);
$canManage  = ($roleId === 1 || $roleId === 6 || in_array($roleName, ['Admin', 'Facilities Manager', 'Facility Manager'], true));
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Equipment & Resources</h1>
        <p class="page-subtitle">Manage physical equipment catalog, AV devices, presentation assets, and room resources.</p>
    </div>

    <?php if ($canManage): ?>
    <button type="button" class="btn-primary-action" id="newEquipmentBtn">
        <i class="bi bi-plus-lg"></i>
        New Equipment
    </button>
    <?php endif; ?>
</div>

<!-- ================================================================
     Equipment Toolbar & Filter Controls
     ================================================================ -->
<div class="booking-toolbar equipment-toolbar d-flex flex-wrap align-items-center gap-2 mb-4">

    <!-- Search Input -->
    <div class="booking-search flex-grow-1" style="min-width: 220px;">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="equipmentSearch"
            placeholder="Search equipment by name, code, serial..."
            aria-label="Search equipment"
        >
    </div>

    <!-- Category Filter -->
    <select id="equipmentCategoryFilter" class="booking-filter" aria-label="Filter by category" style="min-width: 150px;">
        <option value="">All Categories</option>
        <option value="projector">Projector</option>
        <option value="audio">Audio</option>
        <option value="display">Display</option>
        <option value="video">Video</option>
        <option value="adapter">Adapter</option>
        <option value="computer">Computer</option>
        <option value="furniture">Furniture</option>
        <option value="other">Other</option>
    </select>

    <!-- Status Filter -->
    <select id="equipmentStatusFilter" class="booking-filter" aria-label="Filter by status" style="min-width: 140px;">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="maintenance">Maintenance</option>
        <option value="damaged">Damaged</option>
        <option value="retired">Retired</option>
    </select>

    <!-- Location Filter (Dynamic from API) -->
    <select id="equipmentLocationFilter" class="booking-filter" aria-label="Filter by location" style="min-width: 150px;">
        <option value="">All Locations</option>
    </select>

    <!-- Clear Filters & Refresh Action Buttons -->
    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" id="equipmentClearFiltersBtn" title="Clear all filters">
        <i class="bi bi-x-circle"></i>
        <span>Clear</span>
    </button>

    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" id="equipmentRefreshBtn" title="Refresh equipment inventory">
        <i class="bi bi-arrow-clockwise"></i>
        <span>Refresh</span>
    </button>

</div>

<!-- ================================================================
     Main Equipment Inventory Panel
     ================================================================ -->
<div class="panel-card equipment-panel">

    <!-- Loading State -->
    <div id="equipmentLoading" class="bookings-state">
        <div class="meetspace-loader" role="status" aria-live="polite">
            <div class="meetspace-loader-track">
                <div class="meetspace-loader-bar">
                    <div class="meetspace-loader-highlights"></div>
                </div>
            </div>
            <div class="meetspace-loader-text">
                <span class="loader-label">Loading equipment inventory</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div id="equipmentError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load equipment inventory.</span>
        <button type="button" class="btn btn-sm btn-outline-light mt-2" id="equipmentRetryBtn">
            <i class="bi bi-arrow-clockwise me-1"></i>Retry
        </button>
    </div>

    <!-- Empty State -->
    <div id="equipmentEmpty" class="bookings-state d-none">
        <i class="bi bi-tools"></i>
        <span>No equipment items found matching your filters.</span>
    </div>

    <!-- Inventory Table -->
    <div id="equipmentTableWrapper" class="table-responsive d-none">
        <table class="table custom-dark-table equipment-table align-middle mb-0">
            <thead>
                <tr>
                    <th>ASSET / NAME</th>
                    <th>CATEGORY</th>
                    <th>LOCATION & ROOM</th>
                    <th>SERIAL NUMBER</th>
                    <th>STATUS</th>
                    <th style="min-width: 110px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="equipmentTableBody"></tbody>
        </table>
    </div>

</div>


<!-- ================================================================
     Equipment Create / Edit Modal
     ================================================================ -->
<div class="booking-modal-overlay d-none" id="equipmentModal">
    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="equipmentModalTitle"
        style="max-width: 640px;"
    >
        <div class="booking-modal-header">
            <div>
                <h2 id="equipmentModalTitle">New Equipment</h2>
                <p id="equipmentModalSubtitle">Add a new physical equipment asset to the inventory catalog.</p>
            </div>
            <button
                type="button"
                class="booking-modal-close"
                id="closeEquipmentModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="equipmentForm">
            <input type="hidden" id="equipmentId">

            <div id="equipmentFormError" class="booking-form-alert booking-form-error d-none" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle"></i>
                <span id="equipmentFormErrorText"></span>
            </div>

            <div id="equipmentFormSuccess" class="booking-form-alert booking-form-success d-none" role="status" aria-live="polite">
                <i class="bi bi-check-circle"></i>
                <span id="equipmentFormSuccessText"></span>
            </div>

            <div class="booking-form-grid">

                <!-- Name -->
                <div class="booking-form-group">
                    <label for="equipmentName">
                        Equipment Name
                        <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="equipmentName"
                        maxlength="150"
                        placeholder="e.g. 4K Conference Projector"
                        required
                    >
                </div>

                <!-- Code -->
                <div class="booking-form-group">
                    <label for="equipmentCode">
                        Asset Code
                        <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="equipmentCode"
                        maxlength="50"
                        placeholder="e.g. PRJ-001"
                        required
                    >
                </div>

                <!-- Category -->
                <div class="booking-form-group">
                    <label for="equipmentCategory">
                        Category
                        <span class="text-danger">*</span>
                    </label>
                    <select id="equipmentCategory" required>
                        <option value="">Select Category</option>
                        <option value="projector">Projector</option>
                        <option value="audio">Audio</option>
                        <option value="display">Display</option>
                        <option value="video">Video</option>
                        <option value="adapter">Adapter</option>
                        <option value="computer">Computer</option>
                        <option value="furniture">Furniture</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="booking-form-group">
                    <label for="equipmentStatus">
                        Catalog Status
                        <span class="text-danger">*</span>
                    </label>
                    <select id="equipmentStatus" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="damaged">Damaged</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>

                <!-- Model Number -->
                <div class="booking-form-group">
                    <label for="equipmentModelNumber">
                        Model Number
                    </label>
                    <input
                        type="text"
                        id="equipmentModelNumber"
                        maxlength="100"
                        placeholder="e.g. Optoma UHD38"
                    >
                </div>

                <!-- Serial Number -->
                <div class="booking-form-group">
                    <label for="equipmentSerialNumber">
                        Serial Number
                    </label>
                    <input
                        type="text"
                        id="equipmentSerialNumber"
                        maxlength="100"
                        placeholder="e.g. SN-98765432"
                    >
                </div>

                <!-- Location -->
                <div class="booking-form-group">
                    <label for="equipmentLocationId">
                        Location
                    </label>
                    <select id="equipmentLocationId">
                        <option value="">None / Unassigned</option>
                    </select>
                </div>

                <!-- Default Room -->
                <div class="booking-form-group">
                    <label for="equipmentDefaultRoomId">
                        Default Room
                    </label>
                    <select id="equipmentDefaultRoomId">
                        <option value="">None / Floating Asset</option>
                    </select>
                </div>

                <!-- Description -->
                <div class="booking-form-group booking-form-full">
                    <label for="equipmentDescription">
                        Description
                    </label>
                    <textarea
                        id="equipmentDescription"
                        maxlength="2000"
                        placeholder="Device specifications, features, accessories included..."
                        rows="3"
                    ></textarea>
                </div>

                <!-- Notes -->
                <div class="booking-form-group booking-form-full">
                    <label for="equipmentNotes">
                        Internal Notes
                    </label>
                    <textarea
                        id="equipmentNotes"
                        maxlength="2000"
                        placeholder="Maintenance history, storage instructions, warranty info..."
                        rows="2"
                    ></textarea>
                </div>

            </div>

            <div class="booking-modal-footer">
                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelEquipmentBtn"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitEquipmentBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    <span id="submitEquipmentBtnText">Save Equipment</span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ================================================================
     Equipment Details Modal
     ================================================================ -->
<div class="booking-modal-overlay d-none" id="equipmentDetailsModal">
    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="detailsModalTitle"
        style="max-width: 600px;"
    >
        <div class="booking-modal-header">
            <div>
                <h2 id="detailsModalTitle">Equipment Details</h2>
                <p id="detailsModalSubtitle">Asset specifications and placement information.</p>
            </div>
            <button
                type="button"
                class="booking-modal-close"
                id="closeDetailsModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="p-4" id="equipmentDetailsBody">
            <div id="equipmentDetailsContent">
                <!-- Dynamically populated by equipment.js -->
            </div>
        </div>

        <div class="booking-modal-footer">
            <button
                type="button"
                class="btn-booking-cancel"
                id="closeDetailsFooterBtn"
            >
                Close
            </button>
            <?php if ($canManage): ?>
            <button
                type="button"
                class="btn-booking-submit"
                id="detailsEditBtn"
            >
                <i class="bi bi-pencil me-1"></i>
                Edit Equipment
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
