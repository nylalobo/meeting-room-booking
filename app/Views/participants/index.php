<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Participants</h1>
        <p class="page-subtitle">Manage meeting attendees, roles, and response statuses.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newParticipantBtn">
        <i class="bi bi-person-plus"></i>
        Add Participant
    </button>
</div>

<div class="booking-toolbar participants-toolbar">

    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="participantSearch"
            placeholder="Search participants, emails, or meetings..."
            aria-label="Search participants"
        >
    </div>

    <select id="typeFilter" class="booking-filter" aria-label="Filter by role">
        <option value="">All Roles</option>
        <option value="organizer">Organizer</option>
        <option value="participant">Participant</option>
        <option value="guest">Guest</option>
    </select>

    <select id="statusFilter" class="booking-filter" aria-label="Filter by response status">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="accepted">Accepted</option>
        <option value="declined">Declined</option>
        <option value="tentative">Tentative</option>
    </select>

    <select id="bookingFilter" class="booking-filter" aria-label="Filter by meeting">
        <option value="">All Meetings</option>
    </select>

</div>

<div class="panel-card participants-panel">

    <div id="participantsLoading" class="bookings-state">
        <i class="bi bi-arrow-repeat spin"></i>
        Loading participants...
    </div>

    <div id="participantsError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load participants.</span>
    </div>

    <div id="participantsEmpty" class="bookings-state d-none">
        <i class="bi bi-people"></i>
        <span>No participants found.</span>
    </div>

    <div id="participantsTableWrapper" class="table-responsive d-none">

        <table class="table custom-dark-table participants-table align-middle mb-0">

            <thead>
                <tr>
                    <th>ATTENDEE</th>
                    <th>MEETING</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="participantsTableBody"></tbody>

        </table>

    </div>

</div>


<!-- ================================================================
     Participant Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="participantModal">

    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="participantModalTitle"
    >

        <div class="booking-modal-header">

            <div>
                <h2 id="participantModalTitle">Add Participant</h2>
                <p id="participantModalSubtitle">Assign a user to a meeting booking.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeParticipantModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form id="participantForm">

            <input type="hidden" id="participantBookingId">
            <input type="hidden" id="participantUserId">

            <div id="participantFormError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="participantFormErrorText"></span>
            </div>

            <div id="participantFormSuccess" class="booking-form-alert booking-form-success d-none">
                <i class="bi bi-check-circle"></i>
                <span id="participantFormSuccessText"></span>
            </div>


            <div class="booking-form-grid">

                <div class="booking-form-group booking-form-full">
                    <label for="participantBooking">
                        Meeting
                        <span>*</span>
                    </label>

                    <select id="participantBooking" required>
                        <option value="">Select a meeting...</option>
                    </select>
                </div>


                <div class="booking-form-group booking-form-full">
                    <label for="participantUser">
                        User
                        <span>*</span>
                    </label>

                    <select id="participantUser" required>
                        <option value="">Select a user...</option>
                    </select>
                </div>


                <div class="booking-form-group">
                    <label for="participantType">
                        Participant Type
                        <span>*</span>
                    </label>

                    <select id="participantType" required>
                        <option value="participant">Participant</option>
                        <option value="organizer">Organizer</option>
                        <option value="guest">Guest</option>
                    </select>
                </div>


                <div class="booking-form-group">
                    <label for="participantStatus">
                        Response Status
                        <span>*</span>
                    </label>

                    <select id="participantStatus" required>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                        <option value="tentative">Tentative</option>
                    </select>
                </div>

            </div>


            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelParticipantBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitParticipantBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Add Participant
                </button>

            </div>

        </form>

    </div>

</div>

<style>
.participants-panel {
    padding: 0;
    overflow: hidden;
}

.participants-table thead th {
    padding: 15px 18px;
}

.participants-table tbody td {
    padding: 16px 18px;
}

.participant-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.participant-action-btn {
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

.participant-action-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: #ffffff;
}

.participant-delete-btn:hover {
    background-color: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.participant-role {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 600;
    text-transform: capitalize;
}

.participant-role-organizer {
    background-color: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.participant-role-participant {
    background-color: rgba(45, 212, 191, 0.12);
    color: #2dd4bf;
    border: 1px solid rgba(45, 212, 191, 0.25);
}

.participant-role-guest {
    background-color: rgba(245, 158, 11, 0.12);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.25);
}

.booking-status-declined {
    background-color: rgba(239, 68, 68, 0.12);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.25);
}

.booking-status-tentative {
    background-color: rgba(168, 85, 247, 0.15);
    color: #c084fc;
    border: 1px solid rgba(168, 85, 247, 0.25);
}
</style>

<?= $this->endSection() ?>
