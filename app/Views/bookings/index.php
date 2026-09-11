<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Bookings</h1>
        <p class="page-subtitle">Manage and monitor meeting room bookings.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newBookingBtn">
        <i class="bi bi-calendar-plus"></i>
        New Booking
    </button>
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