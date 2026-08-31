<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Rooms</h1>
        <p class="page-subtitle">Manage meeting rooms and their availability.</p>
    </div>

    <button type="button" class="btn-primary-action" id="newRoomBtn">
        <i class="bi bi-plus-lg"></i>
        New Room
    </button>
</div>

<div class="booking-toolbar rooms-toolbar">

    <div class="booking-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            id="roomSearch"
            placeholder="Search rooms..."
            aria-label="Search rooms"
        >
    </div>

    <select id="roomStatusFilter" class="booking-filter" aria-label="Filter by status">
        <option value="">All Statuses</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>

</div>

<div class="panel-card rooms-panel">

    <div id="roomsLoading" class="bookings-state">
        <i class="bi bi-arrow-repeat spin"></i>
        Loading rooms...
    </div>

    <div id="roomsError" class="bookings-state bookings-error d-none">
        <i class="bi bi-exclamation-circle"></i>
        <span>Unable to load rooms.</span>
    </div>

    <div id="roomsEmpty" class="bookings-state d-none">
        <i class="bi bi-door-closed"></i>
        <span>No rooms found.</span>
    </div>

    <div id="roomsTableWrapper" class="table-responsive d-none">

        <table class="table custom-dark-table rooms-table align-middle mb-0">

            <thead>
                <tr>
                    <th>ROOM</th>
                    <th>CODE</th>
                    <th>CAPACITY</th>
                    <th>FLOOR</th>
                    <th>DESCRIPTION</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody id="roomsTableBody"></tbody>

        </table>

    </div>

</div>


<!-- ================================================================
     Room Modal
     ================================================================ -->

<div class="booking-modal-overlay d-none" id="roomModal">

    <div
        class="booking-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="roomModalTitle"
    >

        <div class="booking-modal-header">

            <div>
                <h2 id="roomModalTitle">New Room</h2>
                <p id="roomModalSubtitle">Create a new meeting room.</p>
            </div>

            <button
                type="button"
                class="booking-modal-close"
                id="closeRoomModal"
                aria-label="Close"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <form id="roomForm">

            <input type="hidden" id="roomId">

            <div id="roomFormError" class="booking-form-alert booking-form-error d-none">
                <i class="bi bi-exclamation-circle"></i>
                <span id="roomFormErrorText"></span>
            </div>

            <div id="roomFormSuccess" class="booking-form-alert booking-form-success d-none">
                <i class="bi bi-check-circle"></i>
                <span id="roomFormSuccessText"></span>
            </div>


            <div class="booking-form-grid">

                <div class="booking-form-group">
                    <label for="roomName">
                        Room Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="roomName"
                        maxlength="150"
                        placeholder="e.g. Conference Room A"
                        required
                    >
                </div>


                <div class="booking-form-group">
                    <label for="roomCode">
                        Room Code
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="roomCode"
                        maxlength="50"
                        placeholder="e.g. CRA-001"
                        required
                    >
                </div>


                <div class="booking-form-group">
                    <label for="roomLocation">
                        Location ID
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        id="roomLocation"
                        min="1"
                        placeholder="e.g. 1"
                        required
                    >
                </div>


                <div class="booking-form-group">
                    <label for="roomCapacity">
                        Capacity
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        id="roomCapacity"
                        min="1"
                        placeholder="e.g. 20"
                        required
                    >
                </div>


                <div class="booking-form-group">
                    <label for="roomFloor">
                        Floor
                    </label>

                    <input
                        type="text"
                        id="roomFloor"
                        maxlength="50"
                        placeholder="e.g. 2nd Floor"
                    >
                </div>


                <div class="booking-form-group">
                    <label for="roomStatus">
                        Status
                    </label>

                    <select id="roomStatus">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>


                <div class="booking-form-group booking-form-full">

                    <label for="roomDescription">
                        Description
                    </label>

                    <textarea
                        id="roomDescription"
                        rows="4"
                        placeholder="Add room details..."
                    ></textarea>

                </div>

            </div>


            <div class="booking-modal-footer">

                <button
                    type="button"
                    class="btn-booking-cancel"
                    id="cancelRoomBtn"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-booking-submit"
                    id="submitRoomBtn"
                >
                    <i class="bi bi-check-lg"></i>
                    Save Room
                </button>

            </div>

        </form>

    </div>

</div>

<?= $this->endSection() ?>