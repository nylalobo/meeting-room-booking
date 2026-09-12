<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Bookings</h1>
        <p class="page-subtitle">Manage and monitor meeting room bookings.</p>
    </div>

    <div class="page-header-actions" style="display: flex; gap: 10px; align-items: center;">
        <button type="button" class="btn-secondary-action" id="findAvailableRoomBtn">
            <i class="bi bi-search"></i>
            Find Available Room
        </button>
        <button type="button" class="btn-primary-action" id="newBookingBtn">
            <i class="bi bi-calendar-plus"></i>
            New Booking
        </button>
    </div>
</div>

<div class="booking-toolbar">
    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="bookingSearch"
            placeholder="Search meetings, rooms, or organizers..."
            aria-label="Search bookings"
        >
    </div>

    <select id="bookingRoomFilter" class="booking-filter" aria-label="Filter by room">
        <option value="">All Rooms</option>
    </select>

    <select id="statusFilter" class="booking-filter" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="cancelled">Cancelled</option>
        <option value="completed">Completed</option>
    </select>

    <button type="button" class="btn-pending-filter d-none" id="pendingApprovalsBtn" title="Show only pending approvals awaiting review">
        <i class="bi bi-clock-history"></i>
        <span>Pending Approvals</span>
        <span class="badge pending-count-badge" id="pendingApprovalsCount">0</span>
    </button>
</div>

<div class="panel-card bookings-panel">

    <div id="bookingsLoading" class="bookings-state">
        <i class="bi bi-arrow-repeat spin"></i>
        Loading bookings...
    </div>

    <div id="bookingsError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load bookings.</span>
    </div>

    <div id="bookingsEmpty" class="bookings-state d-none">
        <i class="bi bi-calendar-x"></i>
        <span>No bookings found.</span>
    </div>

    <div id="bookingsTableWrapper" class="table-responsive d-none">
        <table class="table custom-dark-table bookings-table align-middle mb-0">
            <thead>
                <tr>
                    <th>MEETING</th>
                    <th>ROOM</th>
                    <th>DATE</th>
                    <th>TIME</th>
                    <th>ORGANIZER</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="bookingsTableBody"></tbody>
        </table>
    </div>

</div>


<!-- ================================================================
     Booking Modal (Create / Edit)
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="bookingModal">

    <div class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="bookingModalTitle">

        <div class="booking-modal-header">
            <div>
                <h2 id="bookingModalTitle">New Booking</h2>
                <p id="bookingModalSubtitle">Create a new meeting room booking.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeBookingModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>


        <form id="bookingForm">

            <input type="hidden" id="bookingId">

            <div id="bookingFormError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="bookingFormErrorText"></span>
            </div>

            <div id="bookingFormSuccess" class="booking-form-alert booking-form-success d-none">
                <i class="bi bi-check-circle"></i>
                <span id="bookingFormSuccessText"></span>
            </div>


            <div class="booking-form-grid">

                <!-- Meeting title -->
                <div class="booking-form-group booking-form-full">
                    <label for="bookingTitle">
                        Meeting Title
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="bookingTitle"
                        name="title"
                        maxlength="200"
                        placeholder="e.g. Team Planning Meeting"
                        required
                    >
                </div>


                <!-- Room -->
                <div class="booking-form-group">
                    <label for="bookingRoom">
                        Room
                        <span>*</span>
                    </label>

                    <select id="bookingRoom" name="room_id" required>
                        <option value="">Select a room...</option>
                    </select>
                </div>


                <!-- Organizer -->
                <div class="booking-form-group">
                    <label for="bookingUser">
                        Organizer
                        <span>*</span>
                    </label>

                    <select id="bookingUser" name="user_id" required>
                        <option value="">Select organizer...</option>
                    </select>
                </div>


                <!-- Start -->
                <div class="booking-form-group">
                    <label for="bookingStart">
                        Start Time
                        <span>*</span>
                    </label>

                    <input
                        type="datetime-local"
                        id="bookingStart"
                        name="start_time"
                        required
                    >
                </div>


                <!-- End -->
                <div class="booking-form-group">
                    <label for="bookingEnd">
                        End Time
                        <span>*</span>
                    </label>

                    <input
                        type="datetime-local"
                        id="bookingEnd"
                        name="end_time"
                        required
                    >
                </div>


                <!-- Status -->
                <div class="booking-form-group booking-form-full">
                    <label for="bookingStatus">
                        Status
                        <span>*</span>
                    </label>

                    <select id="bookingStatus" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>


                <!-- Description -->
                <div class="booking-form-group booking-form-full">
                    <label for="bookingDescription">
                        Description
                    </label>

                    <textarea
                        id="bookingDescription"
                        name="description"
                        rows="3"
                        placeholder="Add meeting details or notes..."
                    ></textarea>
                </div>

                <!-- Recurrence Toggle Section -->
                <div class="booking-form-group booking-form-full recurrence-toggle-section">
                    <div class="recurrence-toggle-wrapper">
                        <label class="recurrence-toggle-label" for="bookingIsRecurring">
                            <input
                                type="checkbox"
                                id="bookingIsRecurring"
                                name="is_recurring"
                                value="1"
                            >
                            <span class="recurrence-toggle-custom"></span>
                            <span class="recurrence-toggle-text">
                                <i class="bi bi-repeat"></i>
                                Repeat meeting (Recurring Series)
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Recurrence Configuration Container (Progressive Disclosure) -->
                <div id="recurrenceFieldsContainer" class="booking-form-full recurrence-fields-container d-none">

                    <div class="recurrence-card">
                        <div class="recurrence-card-header">
                            <div class="recurrence-card-title">
                                <i class="bi bi-arrow-repeat"></i>
                                Recurrence Configuration
                            </div>
                            <span class="recurrence-badge">Series Options</span>
                        </div>

                        <div class="recurrence-card-body">
                            <div class="recurrence-grid">

                                <!-- Frequency -->
                                <div class="booking-form-group">
                                    <label for="recurrenceFrequency">
                                        Frequency
                                        <span>*</span>
                                    </label>
                                    <select id="recurrenceFrequency" name="recurrence_frequency">
                                        <option value="daily">Daily</option>
                                        <option value="weekly" selected>Weekly</option>
                                        <option value="biweekly">Biweekly (Every 2 weeks)</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="weekdays">Weekdays (Mon – Fri)</option>
                                    </select>
                                </div>

                                <!-- Interval -->
                                <div class="booking-form-group" id="recurrenceIntervalGroup">
                                    <label for="recurrenceInterval">
                                        Repeat Every
                                    </label>
                                    <div class="recurrence-interval-box">
                                        <input
                                            type="number"
                                            id="recurrenceInterval"
                                            name="recurrence_interval"
                                            min="1"
                                            max="52"
                                            value="1"
                                        >
                                        <span id="recurrenceIntervalUnit" class="recurrence-unit-text">week(s)</span>
                                    </div>
                                </div>

                                <!-- Weekday selector for Weekly & Biweekly -->
                                <div class="booking-form-group booking-form-full" id="recurrenceDaysOfWeekGroup">
                                    <label>
                                        Repeat On Days
                                    </label>
                                    <div class="recurrence-weekdays-bar" role="group" aria-label="Days of week">
                                        <button type="button" class="weekday-btn" data-dow="1">Mon</button>
                                        <button type="button" class="weekday-btn" data-dow="2">Tue</button>
                                        <button type="button" class="weekday-btn" data-dow="3">Wed</button>
                                        <button type="button" class="weekday-btn" data-dow="4">Thu</button>
                                        <button type="button" class="weekday-btn" data-dow="5">Fri</button>
                                        <button type="button" class="weekday-btn" data-dow="6">Sat</button>
                                        <button type="button" class="weekday-btn" data-dow="7">Sun</button>
                                    </div>
                                    <small class="recurrence-helper-text">Defaults to start date's day of week if none selected.</small>
                                </div>

                                <!-- End Condition Options -->
                                <div class="booking-form-group booking-form-full">
                                    <label>
                                        Ends
                                        <span>*</span>
                                    </label>
                                    <div class="recurrence-end-options">
                                        <label class="recurrence-radio-label" for="recurrenceEndTypeOccurrences">
                                            <input
                                                type="radio"
                                                name="recurrence_end_type"
                                                id="recurrenceEndTypeOccurrences"
                                                value="occurrences"
                                                checked
                                            >
                                            <span class="recurrence-radio-custom"></span>
                                            <span class="recurrence-radio-text">After</span>
                                            <input
                                                type="number"
                                                id="recurrenceOccurrences"
                                                name="recurrence_occurrences"
                                                min="1"
                                                max="52"
                                                value="5"
                                                class="recurrence-inline-input"
                                                aria-label="Number of occurrences"
                                            >
                                            <span class="recurrence-radio-text">occurrences (max 52)</span>
                                        </label>

                                        <label class="recurrence-radio-label" for="recurrenceEndTypeDate">
                                            <input
                                                type="radio"
                                                name="recurrence_end_type"
                                                id="recurrenceEndTypeDate"
                                                value="date"
                                            >
                                            <span class="recurrence-radio-custom"></span>
                                            <span class="recurrence-radio-text">On date</span>
                                            <input
                                                type="date"
                                                id="recurrenceUntilDate"
                                                name="recurrence_until_date"
                                                class="recurrence-inline-input"
                                                disabled
                                                aria-label="Recurrence end date"
                                            >
                                            <span class="recurrence-radio-text">(up to 1 year)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Monthly note -->
                                <div class="booking-form-group booking-form-full d-none" id="recurrenceMonthlyNote">
                                    <div class="recurrence-info-box">
                                        <i class="bi bi-info-circle"></i>
                                        <span>Occurs monthly on the same day of the month. Months with fewer days automatically adjust safely.</span>
                                    </div>
                                </div>

                                <!-- Preview Action Bar -->
                                <div class="booking-form-group booking-form-full recurrence-actions-wrapper">
                                    <button
                                        type="button"
                                        id="previewRecurrenceBtn"
                                        class="btn-preview-recurrence"
                                    >
                                        <i class="bi bi-eye"></i>
                                        Preview Occurrences & Check Conflicts
                                    </button>
                                </div>

                            </div>
                        </div>

                        <!-- Recurrence Preview Container -->
                        <div id="recurrencePreviewContainer" class="recurrence-preview-container d-none">
                            <!-- Injected dynamically via JS: summary badges, conflict banner, occurrence table -->
                        </div>
                    </div>

                </div>

            </div>


            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelBookingBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitBookingBtn"
                >
                    <i class="bi bi-calendar-check"></i>
                    Create Booking
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ================================================================
     Booking Rejection Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="bookingRejectionModal">

    <div class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="bookingRejectionTitle" style="max-width: 480px;">

        <div class="booking-modal-header">
            <div>
                <h2 id="bookingRejectionTitle">Reject Booking Request</h2>
                <p id="bookingRejectionSubtitle">Provide a clear reason for rejecting this booking.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeBookingRejectionModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="bookingRejectionForm">

            <input type="hidden" id="rejectionBookingId" name="booking_id">

            <div id="bookingRejectionError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="bookingRejectionErrorText"></span>
            </div>

            <div class="booking-form-grid" style="grid-template-columns: 1fr;">

                <div class="booking-form-group booking-form-full">
                    <label for="rejectionReasonInput">
                        Rejection Reason <span>*</span>
                    </label>
                    <textarea
                        id="rejectionReasonInput"
                        name="reason"
                        rows="4"
                        required
                        minlength="3"
                        maxlength="500"
                        placeholder="Please specify why this booking request is being rejected (minimum 3 characters)..."
                    ></textarea>
                    <small style="font-size: 11px; color: var(--color-text-muted, #8496b5); margin-top: 4px; display: block;">
                        Rejection reasons are permanently saved and visible in audit logs.
                    </small>
                </div>

            </div>

            <div class="booking-modal-footer">
                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelBookingRejectionBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-reject-submit"
                    id="submitBookingRejectionBtn"
                >
                    <i class="bi bi-x-circle"></i>
                    Confirm Rejection
                </button>
            </div>

        </form>

    </div>

</div>

<!-- ================================================================
     Room Availability & Search Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="availabilityModal">

    <div class="booking-modal availability-modal" role="dialog" aria-modal="true" aria-labelledby="availabilityModalTitle">

        <div class="booking-modal-header">
            <div>
                <h2 id="availabilityModalTitle">Find Available Room</h2>
                <p id="availabilityModalSubtitle">Search real-time room availability across locations, capacities, and facilities.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeAvailabilityModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="availabilityForm">

            <div id="availabilityError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="availabilityErrorText"></span>
            </div>

            <div class="availability-search-grid">

                <!-- Start Time -->
                <div class="booking-form-group">
                    <label for="availStart">
                        Start Time <span>*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="availStart"
                        name="start_time"
                        required
                    >
                </div>

                <!-- End Time -->
                <div class="booking-form-group">
                    <label for="availEnd">
                        End Time <span>*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="availEnd"
                        name="end_time"
                        required
                    >
                </div>

                <!-- Location Filter -->
                <div class="booking-form-group">
                    <label for="availLocation">
                        Location
                    </label>
                    <select id="availLocation" name="location_id">
                        <option value="">All Locations</option>
                    </select>
                </div>

                <!-- Minimum Capacity -->
                <div class="booking-form-group">
                    <label for="availCapacity">
                        Min Capacity
                    </label>
                    <input
                        type="number"
                        id="availCapacity"
                        name="capacity"
                        min="1"
                        placeholder="e.g. 10"
                    >
                </div>

                <!-- Specific Room Filter (Optional) -->
                <div class="booking-form-group booking-form-full">
                    <label for="availSpecificRoom">
                        Specific Room <span style="font-size: 11px; font-weight: normal; color: var(--color-text-muted, #8496b5);">(Optional — check if a specific room is free)</span>
                    </label>
                    <select id="availSpecificRoom" name="room_id">
                        <option value="">Any Room</option>
                    </select>
                </div>

                <!-- Facilities Filter (Optional) -->
                <div class="booking-form-group booking-form-full">
                    <label>
                        Required Facilities
                    </label>
                    <div class="facilities-checkbox-grid" id="availFacilitiesList">
                        <!-- Populated dynamically via JS from /api/facilities -->
                    </div>
                </div>

            </div>

            <div class="availability-search-actions">
                <button
                    type="submit"
                    class="btn-availability-search"
                    id="submitAvailabilitySearchBtn"
                >
                    <i class="bi bi-search"></i>
                    Search Available Rooms
                </button>
            </div>

        </form>

        <!-- Availability Results Container -->
        <div id="availabilityResultsContainer" class="availability-results-section d-none">

            <div id="availabilityLoading" class="availability-state d-none">
                <i class="bi bi-arrow-repeat spin"></i>
                Checking real-time room availability...
            </div>

            <!-- Requested Room Conflict Banner (when a specific room is booked) -->
            <div id="requestedRoomUnavailableAlert" class="requested-room-alert d-none">
                <div class="requested-room-alert-icon">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <div class="requested-room-alert-content">
                    <h4 id="unavailableRoomName">Conference Room is Unavailable</h4>
                    <p id="unavailableRoomReason">This room has conflicting bookings during the requested timeframe.</p>
                    <div id="unavailableRoomConflicts" class="conflict-intervals"></div>
                </div>
            </div>

            <!-- Requested Room Available Banner -->
            <div id="requestedRoomAvailableAlert" class="requested-room-success d-none">
                <div class="requested-room-alert-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="requested-room-alert-content">
                    <h4 id="availableRoomName">Room is Available!</h4>
                    <p>The requested room is completely free during this period.</p>
                </div>
                <button type="button" class="btn-book-room-now" id="bookRequestedRoomBtn">
                    <i class="bi bi-calendar-check"></i> Book Now
                </button>
            </div>

            <!-- Alternative Room Suggestions Header -->
            <div id="alternativeSuggestionsWrapper" class="alternatives-section d-none">
                <div class="alternatives-header">
                    <div class="alternatives-title-group">
                        <i class="bi bi-lightbulb"></i>
                        <h3>Recommended Alternative Rooms</h3>
                    </div>
                    <span class="badge alternatives-count-badge" id="alternativesCount">0</span>
                </div>
                <p class="alternatives-subtitle">These available rooms match your meeting's location, capacity, and facility requirements.</p>

                <div class="available-rooms-grid" id="alternativeRoomsList">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Available Rooms Section -->
            <div id="availableRoomsWrapper" class="available-section d-none">
                <div class="available-header">
                    <div class="available-title-group">
                        <i class="bi bi-door-open"></i>
                        <h3>Available Rooms</h3>
                    </div>
                    <span class="badge available-count-badge" id="availableCount">0</span>
                </div>

                <div class="available-rooms-grid" id="availableRoomsList">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Empty State -->
            <div id="availabilityEmptyState" class="availability-empty d-none">
                <i class="bi bi-building-x"></i>
                <h4>No Available Rooms Found</h4>
                <p>No active rooms match all of your selected time, capacity, location, and facility criteria. Try expanding your search criteria or choosing a different time.</p>
            </div>

        </div>

    </div>

</div>

<style>
.bookings-panel {
    padding: 0;
    overflow: hidden;
}

.bookings-table thead th {
    padding: 15px 18px;
}

.bookings-table tbody td {
    padding: 16px 18px;
}

.booking-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.booking-action-btn {
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

.booking-action-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}

.booking-delete-btn:hover {
    background-color: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.booking-status-pending {
    background-color: rgba(234, 179, 8, 0.12);
    color: #facc15;
    border: 1px solid rgba(234, 179, 8, 0.25);
}

.booking-status-approved {
    background-color: rgba(34, 197, 94, 0.12);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.25);
}

.booking-status-rejected {
    background-color: rgba(239, 68, 68, 0.12);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.25);
}

.booking-status-cancelled {
    background-color: rgba(148, 163, 184, 0.12);
    color: #94a3b8;
    border: 1px solid rgba(148, 163, 184, 0.25);
}

.booking-status-completed {
    background-color: rgba(59, 130, 246, 0.12);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.25);
}

.btn-pending-filter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 6px;
    border: 1px solid rgba(234, 179, 8, 0.35);
    background-color: rgba(234, 179, 8, 0.08);
    color: #facc15;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-pending-filter:hover {
    background-color: rgba(234, 179, 8, 0.16);
    border-color: rgba(234, 179, 8, 0.6);
}

.btn-pending-filter.active {
    background-color: #eab308;
    color: #0f172a;
    border-color: #ca8a04;
    font-weight: 600;
}

.btn-pending-filter .pending-count-badge {
    background-color: #eab308;
    color: #0f172a;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 12px;
}

.btn-pending-filter.active .pending-count-badge {
    background-color: #0f172a;
    color: #facc15;
}

.booking-approve-btn:hover {
    background-color: rgba(34, 197, 94, 0.18) !important;
    border-color: rgba(34, 197, 94, 0.4) !important;
    color: #4ade80 !important;
}

.booking-reject-btn:hover {
    background-color: rgba(239, 68, 68, 0.18) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
    color: #f87171 !important;
}

.btn-booking-reject-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    background-color: #dc2626;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-booking-reject-submit:hover {
    background-color: #b91c1c;
}

.btn-booking-reject-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.booking-meta-note {
    font-size: 11px;
    margin-top: 4px;
    display: block;
}

.booking-meta-rejected {
    color: #f87171;
}

.booking-meta-approver {
    color: var(--color-text-muted, #8496b5);
}
</style>

<?= $this->endSection() ?>
