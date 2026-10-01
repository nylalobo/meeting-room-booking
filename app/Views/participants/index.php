<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Participants</h1>
        <p class="page-subtitle">See who's attending each meeting and event.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newParticipantBtn">
        <i class="bi bi-person-plus"></i>
        Add Participant
    </button>
</div>

<!-- Summary Metric Cards -->
<div class="row g-3 participants-metrics-row stagger-children">
    <div class="col-6 col-md-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">TOTAL MEETINGS</div>
                <div class="stat-value" id="metricTotalMeetings">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-cyan">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">TOTAL ATTENDEES</div>
                <div class="stat-value" id="metricTotalParticipants">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">UPCOMING MEETINGS</div>
                <div class="stat-value" id="metricUpcomingMeetings">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card glass-card hover-lift">
            <div class="stat-icon-wrapper stat-icon-amber">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-meta">
                <div class="stat-label">PENDING RESPONSES</div>
                <div class="stat-value" id="metricPendingResponses">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filtering Toolbar -->
<div class="booking-toolbar participants-toolbar">

    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="participantSearch"
            placeholder="Search meetings, attendees, rooms, departments..."
            aria-label="Search meetings and attendees"
        >
    </div>

    <select id="statusFilter" class="booking-filter" aria-label="Filter by meeting status">
        <option value="">All Statuses</option>
        <option value="approved">Approved</option>
        <option value="pending">Pending</option>
        <option value="rejected">Rejected</option>
        <option value="cancelled">Cancelled</option>
    </select>

    <select id="dateFilter" class="booking-filter" aria-label="Filter by date">
        <option value="">All Dates</option>
        <option value="today">Today</option>
        <option value="upcoming">Upcoming</option>
        <option value="past">Past</option>
    </select>

    <select id="typeFilter" class="booking-filter" aria-label="Filter by role">
        <option value="">All Roles</option>
        <option value="organizer">Organizer</option>
        <option value="participant">Participant</option>
        <option value="guest">Guest</option>
    </select>

    <select id="bookingFilter" class="booking-filter" aria-label="Filter by meeting">
        <option value="">All Meetings</option>
    </select>

</div>

<!-- Main Meeting Cards Presentation -->
<div class="participants-container">

    <div id="participantsLoading" class="bookings-state">
        <div class="meetspace-loader" role="status" aria-live="polite">
            <div class="meetspace-loader-track">
                <div class="meetspace-loader-bar">
                    <div class="meetspace-loader-highlights"></div>
                </div>
            </div>
            <div class="meetspace-loader-text">
                <span class="loader-label">Loading meeting attendees</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <div id="participantsError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load meeting participants.</span>
    </div>

    <div id="participantsEmpty" class="bookings-state d-none">
        <i class="bi bi-calendar-x"></i>
        <span>No meetings found matching your search and filter criteria.</span>
    </div>

    <!-- Dynamic Meeting Cards Grid -->
    <div id="participantsCardsContainer" class="participants-grid d-none"></div>

    <!-- Hidden compatibility wrapper for legacy checks -->
    <div id="participantsTableWrapper" class="d-none" aria-hidden="true">
        <tbody id="participantsTableBody"></tbody>
    </div>

</div>


<!-- ================================================================
     Participant Modal (Preserved for Add/Edit Actions)
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
                <p id="participantModalSubtitle">Assign an attendee to a meeting booking.</p>
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


        <form id="participantForm" novalidate>

            <input type="hidden" id="participantBookingId">
            <input type="hidden" id="participantUserId">

            <div id="participantFormError" class="booking-form-alert booking-form-error d-none" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle"></i>
                <span id="participantFormErrorText"></span>
            </div>

            <div id="participantFormSuccess" class="booking-form-alert booking-form-success d-none" role="status" aria-live="polite">
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
                        Attendee
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

<?= $this->endSection() ?>
