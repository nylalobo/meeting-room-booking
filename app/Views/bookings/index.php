<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Bookings</h1>
        <p class="page-subtitle">Manage and monitor meeting room bookings.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newBookingBtn">
        <i class="bi bi-plus-lg"></i>
        New Booking
    </button>
</div>

<div class="booking-toolbar">
    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="bookingSearch"
            placeholder="Search bookings..."
            aria-label="Search bookings"
        >
    </div>

    <select id="statusFilter" class="booking-filter" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="cancelled">Cancelled</option>
        <option value="completed">Completed</option>
    </select>
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
                </tr>
            </thead>

            <tbody id="bookingsTableBody"></tbody>
        </table>
    </div>

</div>


<!-- ================================================================
     New Booking Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="bookingModal">

    <div class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="bookingModalTitle">

        <div class="booking-modal-header">
            <div>
                <h2 id="bookingModalTitle">New Booking</h2>
                <p>Create a new meeting room booking.</p>
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


        <form id="newBookingForm">

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
                        <option value="">Loading rooms...</option>
                    </select>
                </div>


                <!-- Organizer -->
                <div class="booking-form-group">
                    <label for="bookingUser">
                        Organizer
                        <span>*</span>
                    </label>

                    <select id="bookingUser" name="user_id" required>
                        <option value="">Loading users...</option>
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


                <!-- Description -->
                <div class="booking-form-group booking-form-full">
                    <label for="bookingDescription">
                        Description
                    </label>

                    <textarea
                        id="bookingDescription"
                        name="description"
                        rows="4"
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

<?= $this->endSection() ?>