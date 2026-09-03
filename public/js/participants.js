/**
 * MeetSpace Enterprise Suite - Booking Participants Module JavaScript
 *
 * Handles:
 * - Loading participants, bookings, and users via Promise.all
 * - Dynamic enrichment of attendee and meeting details
 * - Participant search and multi-criteria filtering
 * - Add/Edit modal with composite key (booking_id, user_id)
 * - Removing participants with custom confirmation dialog
 * - Inline form messages and global application notifications
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('participantsTableBody')) {
            loadParticipantsData();
            setupParticipantFilters();
            setupParticipantModal();
        }
    });


/* ==========================================================================
   STATE
   ========================================================================== */

let allParticipants = [];
let allBookings = [];
let allUsers = [];
let bookingsMap = new Map();
let usersMap = new Map();


/* ==========================================================================
   DATA LOADING
   ========================================================================== */

async function loadParticipantsData() {

    const loading =
        document.getElementById(
            'participantsLoading'
        );

    const error =
        document.getElementById(
            'participantsError'
        );

    const empty =
        document.getElementById(
            'participantsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'participantsTableWrapper'
        );

    try {

        loading?.classList.remove(
            'd-none'
        );

        error?.classList.add(
            'd-none'
        );

        empty?.classList.add(
            'd-none'
        );

        tableWrapper?.classList.add(
            'd-none'
        );

        const [
            participantsRes,
            bookingsRes,
            usersRes
        ] = await Promise.all([
            fetch('/booking-participants'),
            fetch('/api/bookings'),
            fetch('/users')
        ]);

        if (
            !participantsRes.ok ||
            !bookingsRes.ok ||
            !usersRes.ok
        ) {
            throw new Error(
                'One or more participant data sources failed to load.'
            );
        }

        const [
            participantsResult,
            bookingsResult,
            usersResult
        ] = await Promise.all([
            participantsRes.json(),
            bookingsRes.json(),
            usersRes.json()
        ]);

        allParticipants =
            participantsResult.data || [];

        allBookings =
            bookingsResult.data || [];

        allUsers =
            usersResult.data || [];

        bookingsMap = new Map(
            allBookings.map((b) => [
                String(b.id),
                b
            ])
        );

        usersMap = new Map(
            allUsers.map((u) => [
                String(u.id),
                u
            ])
        );

        populateFilterDropdowns();
        populateModalDropdowns();

        loading?.classList.add(
            'd-none'
        );

        renderParticipants(
            allParticipants
        );

    } catch (err) {

        console.error(
            'Unable to load participants data:',
            err
        );

        loading?.classList.add(
            'd-none'
        );

        tableWrapper?.classList.add(
            'd-none'
        );

        empty?.classList.add(
            'd-none'
        );

        error?.classList.remove(
            'd-none'
        );
    }
}


/* ==========================================================================
   RENDERING
   ========================================================================== */

function renderParticipants(participants) {

    const tableBody =
        document.getElementById(
            'participantsTableBody'
        );

    const empty =
        document.getElementById(
            'participantsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'participantsTableWrapper'
        );

    if (!tableBody) {
        return;
    }

    tableBody.innerHTML = '';

    if (!participants.length) {

        tableWrapper?.classList.add(
            'd-none'
        );

        empty?.classList.remove(
            'd-none'
        );

        return;
    }

    empty?.classList.add(
        'd-none'
    );

    tableWrapper?.classList.remove(
        'd-none'
    );

    participants.forEach((participant) => {

        const row =
            document.createElement(
                'tr'
            );

        const booking =
            bookingsMap.get(
                String(
                    participant.booking_id
                )
            );

        const user =
            usersMap.get(
                String(
                    participant.user_id
                )
            );

        const userName =
            user
                ? `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
                  user.email ||
                  `User #${participant.user_id}`
                : `User #${participant.user_id}`;

        const userEmail =
            user?.email || '—';

        const bookingTitle =
            booking?.title ||
            `Booking #${participant.booking_id}`;

        let meetingSubtitle = '—';

        if (booking?.start_time) {

            const startDate =
                new Date(
                    String(
                        booking.start_time
                    ).replace(' ', 'T')
                );

            const datePart =
                formatBookingDate(
                    startDate
                );

            const timePart =
                formatBookingTime(
                    startDate
                );

            meetingSubtitle = `${datePart} • ${timePart}`;

            if (booking.room_name) {
                meetingSubtitle += ` (${booking.room_name})`;
            }
        }

        const role =
            String(
                participant.participant_type ||
                'participant'
            ).toLowerCase();

        const roleLabel =
            role.charAt(0).toUpperCase() +
            role.slice(1);

        const status =
            String(
                participant.response_status ||
                'pending'
            ).toLowerCase();

        const statusLabel =
            status.charAt(0).toUpperCase() +
            status.slice(1);

        const statusClass =
            status === 'declined'
                ? 'declined'
                : status === 'tentative'
                ? 'tentative'
                : status === 'accepted'
                ? 'accepted'
                : 'pending';

        row.innerHTML = `

            <td>

                <div class="booking-room-name">
                    ${escapeHtml(userName)}
                </div>

                <div class="booking-description">
                    ${escapeHtml(userEmail)}
                </div>

            </td>

            <td>

                <div class="booking-title">
                    ${escapeHtml(bookingTitle)}
                </div>

                <div class="booking-description">
                    ${escapeHtml(meetingSubtitle)}
                </div>

            </td>

            <td>

                <span class="participant-role participant-role-${escapeHtml(role)}">
                    ${escapeHtml(roleLabel)}
                </span>

            </td>

            <td>

                <span class="booking-status booking-status-${escapeHtml(statusClass)}">
                    ${escapeHtml(statusLabel)}
                </span>

            </td>

            <td>

                <div class="participant-actions">

                    <button
                        type="button"
                        class="participant-action-btn participant-edit-btn"
                        data-booking-id="${escapeHtml(participant.booking_id)}"
                        data-user-id="${escapeHtml(participant.user_id)}"
                        title="Edit participant"
                        aria-label="Edit participant"
                    >
                        <i class="bi bi-pencil"></i>
                    </button>

                    <button
                        type="button"
                        class="participant-action-btn participant-delete-btn"
                        data-booking-id="${escapeHtml(participant.booking_id)}"
                        data-user-id="${escapeHtml(participant.user_id)}"
                        title="Remove participant"
                        aria-label="Remove participant"
                    >
                        <i class="bi bi-trash"></i>
                    </button>

                </div>

            </td>

        `;

        tableBody.appendChild(row);
    });

    setupParticipantActionButtons();
}


/* ==========================================================================
   DROPDOWNS
   ========================================================================== */

function populateFilterDropdowns() {

    const bookingFilter =
        document.getElementById(
            'bookingFilter'
        );

    if (!bookingFilter) {
        return;
    }

    const currentSelected =
        bookingFilter.value;

    bookingFilter.innerHTML =
        '<option value="">All Meetings</option>';

    allBookings.forEach((b) => {

        const option =
            document.createElement(
                'option'
            );

        option.value =
            String(b.id);

        let label =
            b.title ||
            `Meeting #${b.id}`;

        if (b.start_time) {

            const date =
                new Date(
                    String(
                        b.start_time
                    ).replace(' ', 'T')
                );

            const dateStr =
                formatBookingDate(date);

            if (dateStr !== '—') {
                label += ` (${dateStr})`;
            }
        }

        option.textContent = label;

        bookingFilter.appendChild(
            option
        );
    });

    if (currentSelected) {
        bookingFilter.value =
            currentSelected;
    }
}


function populateModalDropdowns() {

    const bookingSelect =
        document.getElementById(
            'participantBooking'
        );

    const userSelect =
        document.getElementById(
            'participantUser'
        );

    if (bookingSelect) {

        bookingSelect.innerHTML =
            '<option value="">Select a meeting...</option>';

        allBookings.forEach((b) => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                String(b.id);

            let label =
                b.title ||
                `Meeting #${b.id}`;

            if (b.start_time) {

                const date =
                    new Date(
                        String(
                            b.start_time
                        ).replace(' ', 'T')
                    );

                const dateStr =
                    formatBookingDate(date);

                if (dateStr !== '—') {
                    label += ` — ${dateStr}`;
                }
            }

            option.textContent =
                label;

            bookingSelect.appendChild(
                option
            );
        });
    }

    if (userSelect) {

        userSelect.innerHTML =
            '<option value="">Select a user...</option>';

        allUsers.forEach((u) => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                String(u.id);

            const fullName =
                `${u.first_name || ''} ${u.last_name || ''}`.trim() ||
                `User #${u.id}`;

            option.textContent =
                u.email
                    ? `${fullName} (${u.email})`
                    : fullName;

            userSelect.appendChild(
                option
            );
        });
    }
}


/* ==========================================================================
   FILTERS
   ========================================================================== */

function setupParticipantFilters() {

    const searchInput =
        document.getElementById(
            'participantSearch'
        );

    const typeFilter =
        document.getElementById(
            'typeFilter'
        );

    const statusFilter =
        document.getElementById(
            'statusFilter'
        );

    const bookingFilter =
        document.getElementById(
            'bookingFilter'
        );

    searchInput?.addEventListener(
        'input',
        applyParticipantFilters
    );

    typeFilter?.addEventListener(
        'change',
        applyParticipantFilters
    );

    statusFilter?.addEventListener(
        'change',
        applyParticipantFilters
    );

    bookingFilter?.addEventListener(
        'change',
        applyParticipantFilters
    );
}


function applyParticipantFilters() {

    const searchInput =
        document.getElementById(
            'participantSearch'
        );

    const typeFilter =
        document.getElementById(
            'typeFilter'
        );

    const statusFilter =
        document.getElementById(
            'statusFilter'
        );

    const bookingFilter =
        document.getElementById(
            'bookingFilter'
        );

    const searchTerm =
        searchInput
            ? searchInput.value
                .trim()
                .toLowerCase()
            : '';

    const selectedType =
        typeFilter
            ? typeFilter.value.toLowerCase()
            : '';

    const selectedStatus =
        statusFilter
            ? statusFilter.value.toLowerCase()
            : '';

    const selectedBooking =
        bookingFilter
            ? bookingFilter.value
            : '';

    const filtered =
        allParticipants.filter(
            (participant) => {

                const user =
                    usersMap.get(
                        String(
                            participant.user_id
                        )
                    );

                const booking =
                    bookingsMap.get(
                        String(
                            participant.booking_id
                        )
                    );

                const userName =
                    user
                        ? `${user.first_name || ''} ${user.last_name || ''}`.trim()
                        : '';

                const userEmail =
                    user?.email || '';

                const bookingTitle =
                    booking?.title || '';

                const participantType =
                    String(
                        participant.participant_type ||
                        ''
                    ).toLowerCase();

                const responseStatus =
                    String(
                        participant.response_status ||
                        ''
                    ).toLowerCase();

                const searchableText = [
                    userName,
                    userEmail,
                    bookingTitle,
                    participantType,
                    responseStatus
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                const matchesSearch =
                    !searchTerm ||
                    searchableText.includes(
                        searchTerm
                    );

                const matchesType =
                    !selectedType ||
                    participantType ===
                    selectedType;

                const matchesStatus =
                    !selectedStatus ||
                    responseStatus ===
                    selectedStatus;

                const matchesBooking =
                    !selectedBooking ||
                    String(
                        participant.booking_id
                    ) ===
                    selectedBooking;

                return (
                    matchesSearch &&
                    matchesType &&
                    matchesStatus &&
                    matchesBooking
                );
            }
        );

    renderParticipants(filtered);
}


/* ==========================================================================
   MODAL
   ========================================================================== */

function setupParticipantModal() {

    const modal =
        document.getElementById(
            'participantModal'
        );

    const newBtn =
        document.getElementById(
            'newParticipantBtn'
        );

    const closeBtn =
        document.getElementById(
            'closeParticipantModal'
        );

    const cancelBtn =
        document.getElementById(
            'cancelParticipantBtn'
        );

    const form =
        document.getElementById(
            'participantForm'
        );

    if (
        !modal ||
        !newBtn ||
        !form
    ) {
        return;
    }

    newBtn.addEventListener(
        'click',
        () => {

            resetParticipantForm();

            openParticipantModal();
        }
    );

    closeBtn?.addEventListener(
        'click',
        closeParticipantModalWindow
    );

    cancelBtn?.addEventListener(
        'click',
        closeParticipantModalWindow
    );

    modal.addEventListener(
        'click',
        (event) => {

            if (
                event.target === modal
            ) {
                closeParticipantModalWindow();
            }
        }
    );

    document.addEventListener(
        'keydown',
        (event) => {

            if (
                event.key === 'Escape' &&
                !modal.classList.contains(
                    'd-none'
                )
            ) {
                closeParticipantModalWindow();
            }
        }
    );

    form.addEventListener(
        'submit',
        handleParticipantFormSubmit
    );
}


function openParticipantModal() {

    const modal =
        document.getElementById(
            'participantModal'
        );

    if (!modal) {
        return;
    }

    modal.classList.remove(
        'd-none'
    );

    document.body.classList.add(
        'booking-modal-open'
    );

    setTimeout(() => {

        const firstInput =
            document.getElementById(
                'participantBooking'
            );

        if (
            firstInput &&
            !firstInput.disabled
        ) {
            firstInput.focus();
        } else {
            document.getElementById(
                'participantType'
            )?.focus();
        }

    }, 50);
}


function closeParticipantModalWindow() {

    const modal =
        document.getElementById(
            'participantModal'
        );

    if (!modal) {
        return;
    }

    modal.classList.add(
        'd-none'
    );

    document.body.classList.remove(
        'booking-modal-open'
    );
}


function resetParticipantForm() {

    const form =
        document.getElementById(
            'participantForm'
        );

    form?.reset();

    const hiddenBookingId =
        document.getElementById(
            'participantBookingId'
        );

    const hiddenUserId =
        document.getElementById(
            'participantUserId'
        );

    const bookingSelect =
        document.getElementById(
            'participantBooking'
        );

    const userSelect =
        document.getElementById(
            'participantUser'
        );

    const typeSelect =
        document.getElementById(
            'participantType'
        );

    const statusSelect =
        document.getElementById(
            'participantStatus'
        );

    if (hiddenBookingId) {
        hiddenBookingId.value = '';
    }

    if (hiddenUserId) {
        hiddenUserId.value = '';
    }

    if (bookingSelect) {
        bookingSelect.disabled =
            false;
    }

    if (userSelect) {
        userSelect.disabled =
            false;
    }

    if (typeSelect) {
        typeSelect.value =
            'participant';
    }

    if (statusSelect) {
        statusSelect.value =
            'pending';
    }

    const modalTitle =
        document.getElementById(
            'participantModalTitle'
        );

    if (modalTitle) {
        modalTitle.textContent =
            'Add Participant';
    }

    const modalSubtitle =
        document.getElementById(
            'participantModalSubtitle'
        );

    if (modalSubtitle) {
        modalSubtitle.textContent =
            'Assign a user to a meeting booking.';
    }

    const submitBtn =
        document.getElementById(
            'submitParticipantBtn'
        );

    if (submitBtn) {

        submitBtn.disabled =
            false;

        submitBtn.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Add Participant
        `;
    }

    hideParticipantFormMessages();
}


/* ==========================================================================
   ACTION BUTTONS
   ========================================================================== */

function setupParticipantActionButtons() {

    const editButtons =
        document.querySelectorAll(
            '.participant-edit-btn'
        );

    const deleteButtons =
        document.querySelectorAll(
            '.participant-delete-btn'
        );

    editButtons.forEach((btn) => {

        btn.addEventListener(
            'click',
            () => {

                const bookingId =
                    btn.dataset.bookingId;

                const userId =
                    btn.dataset.userId;

                editParticipant(
                    bookingId,
                    userId
                );
            }
        );
    });

    deleteButtons.forEach((btn) => {

        btn.addEventListener(
            'click',
            () => {

                const bookingId =
                    btn.dataset.bookingId;

                const userId =
                    btn.dataset.userId;

                deleteParticipant(
                    bookingId,
                    userId
                );
            }
        );
    });
}


function findParticipant(bookingId, userId) {

    return allParticipants.find(
        (p) =>
            String(p.booking_id) ===
                String(bookingId) &&
            String(p.user_id) ===
                String(userId)
    );
}


/* ==========================================================================
   EDIT PARTICIPANT
   ========================================================================== */

function editParticipant(bookingId, userId) {

    const participant =
        findParticipant(
            bookingId,
            userId
        );

    if (!participant) {

        showAppNotification(
            'Unable to find the selected participant.',
            'error',
            'Participant Not Found'
        );

        return;
    }

    const hiddenBookingId =
        document.getElementById(
            'participantBookingId'
        );

    const hiddenUserId =
        document.getElementById(
            'participantUserId'
        );

    const bookingSelect =
        document.getElementById(
            'participantBooking'
        );

    const userSelect =
        document.getElementById(
            'participantUser'
        );

    const typeSelect =
        document.getElementById(
            'participantType'
        );

    const statusSelect =
        document.getElementById(
            'participantStatus'
        );

    if (hiddenBookingId) {
        hiddenBookingId.value =
            String(bookingId);
    }

    if (hiddenUserId) {
        hiddenUserId.value =
            String(userId);
    }

    if (bookingSelect) {

        bookingSelect.value =
            String(bookingId);

        bookingSelect.disabled =
            true;
    }

    if (userSelect) {

        userSelect.value =
            String(userId);

        userSelect.disabled =
            true;
    }

    if (typeSelect) {
        typeSelect.value =
            participant.participant_type ||
            'participant';
    }

    if (statusSelect) {
        statusSelect.value =
            participant.response_status ||
            'pending';
    }

    const modalTitle =
        document.getElementById(
            'participantModalTitle'
        );

    if (modalTitle) {
        modalTitle.textContent =
            'Edit Participant';
    }

    const modalSubtitle =
        document.getElementById(
            'participantModalSubtitle'
        );

    if (modalSubtitle) {
        modalSubtitle.textContent =
            'Update participant role and response status.';
    }

    const submitBtn =
        document.getElementById(
            'submitParticipantBtn'
        );

    if (submitBtn) {

        submitBtn.disabled =
            false;

        submitBtn.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Changes
        `;
    }

    hideParticipantFormMessages();

    openParticipantModal();
}


/* ==========================================================================
   CREATE / UPDATE PARTICIPANT
   ========================================================================== */

async function handleParticipantFormSubmit(event) {

    event.preventDefault();

    const form =
        document.getElementById(
            'participantForm'
        );

    const submitButton =
        document.getElementById(
            'submitParticipantBtn'
        );

    if (
        !form ||
        !submitButton
    ) {
        return;
    }

    hideParticipantFormMessages();

    const hiddenBookingId =
        document.getElementById(
            'participantBookingId'
        ).value.trim();

    const hiddenUserId =
        document.getElementById(
            'participantUserId'
        ).value.trim();

    const bookingSelect =
        document.getElementById(
            'participantBooking'
        );

    const userSelect =
        document.getElementById(
            'participantUser'
        );

    const typeSelect =
        document.getElementById(
            'participantType'
        );

    const statusSelect =
        document.getElementById(
            'participantStatus'
        );

    const isEditing =
        Boolean(
            hiddenBookingId &&
            hiddenUserId
        );

    const bookingId =
        isEditing
            ? hiddenBookingId
            : bookingSelect.value.trim();

    const userId =
        isEditing
            ? hiddenUserId
            : userSelect.value.trim();

    const participantType =
        typeSelect.value.trim();

    const responseStatus =
        statusSelect.value.trim();

    if (!bookingId) {
        showParticipantFormError(
            'Please select a meeting.'
        );
        return;
    }

    if (!userId) {
        showParticipantFormError(
            'Please select a user.'
        );
        return;
    }

    if (!participantType) {
        showParticipantFormError(
            'Please select a participant type.'
        );
        return;
    }

    if (!responseStatus) {
        showParticipantFormError(
            'Please select a response status.'
        );
        return;
    }

    const payload =
        isEditing
            ? {
                  participant_type:
                      participantType,
                  response_status:
                      responseStatus
              }
            : {
                  booking_id:
                      Number(bookingId),
                  user_id:
                      Number(userId),
                  participant_type:
                      participantType,
                  response_status:
                      responseStatus
              };

    const url =
        isEditing
            ? `/booking-participants/${bookingId}/${userId}`
            : '/booking-participants';

    const method =
        isEditing
            ? 'PUT'
            : 'POST';

    try {

        submitButton.disabled =
            true;

        submitButton.innerHTML = `
            <i class="bi bi-arrow-repeat spin"></i>
            ${
                isEditing
                    ? 'Saving...'
                    : 'Adding...'
            }
        `;

        const response =
            await fetch(
                url,
                {
                    method:
                        method,

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json'
                    },

                    body:
                        JSON.stringify(
                            payload
                        )
                }
            );

        const result =
            await response.json();

        if (
            !response.ok ||
            result.status !==
            'success'
        ) {

            handleParticipantApiError(
                response.status,
                result
            );

            return;
        }

        const successMessage =
            result.message ||
            (
                isEditing
                    ? 'Booking participant updated successfully.'
                    : 'Booking participant created successfully.'
            );

        showParticipantFormSuccess(
            successMessage
        );

        showAppNotification(
            successMessage,
            'success',
            isEditing
                ? 'Participant Updated'
                : 'Participant Added'
        );

        await loadParticipantsData();

        setTimeout(() => {

            closeParticipantModalWindow();

        }, 800);

    } catch (error) {

        console.error(
            'Unable to save participant:',
            error
        );

        showParticipantFormError(
            'Unable to save participant. Please try again.'
        );

        showAppNotification(
            'Unable to save participant. Please try again.',
            'error',
            'Participant Save Failed'
        );

    } finally {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            ${
                isEditing
                    ? 'Save Changes'
                    : 'Add Participant'
            }
        `;
    }
}


/* ==========================================================================
   DELETE PARTICIPANT
   ========================================================================== */

async function deleteParticipant(bookingId, userId) {

    const participant =
        findParticipant(
            bookingId,
            userId
        );

    if (!participant) {

        showAppNotification(
            'Unable to find the selected participant.',
            'error',
            'Participant Not Found'
        );

        return;
    }

    const user =
        usersMap.get(
            String(userId)
        );

    const booking =
        bookingsMap.get(
            String(bookingId)
        );

    const attendeeName =
        user
            ? `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
              user.email ||
              `User #${userId}`
            : `User #${userId}`;

    const bookingTitle =
        booking?.title ||
        `Booking #${bookingId}`;

    showAppConfirm(

        `Are you sure you want to remove "${attendeeName}" from "${bookingTitle}"?`,

        async () => {

            await performParticipantDelete(
                bookingId,
                userId,
                attendeeName
            );
        },

        'Remove Participant',

        'Remove Participant'
    );
}


async function performParticipantDelete(
    bookingId,
    userId,
    attendeeName
) {

    try {

        const response =
            await fetch(
                `/booking-participants/${bookingId}/${userId}`,
                {
                    method:
                        'DELETE',

                    headers: {
                        'Accept':
                            'application/json'
                    }
                }
            );

        const result =
            await response.json();

        if (
            !response.ok ||
            result.status !==
            'success'
        ) {

            throw new Error(
                result.message ||
                'Unable to remove participant.'
            );
        }

        await loadParticipantsData();

        showAppNotification(

            result.message ||
            `"${attendeeName}" has been removed from the meeting.`,

            'success',

            'Participant Removed'
        );

    } catch (error) {

        console.error(
            'Unable to delete participant:',
            error
        );

        showAppNotification(

            error.message ||
            'Unable to remove participant. Please try again.',

            'error',

            'Remove Failed'
        );
    }
}


/* ==========================================================================
   API ERROR HANDLING
   ========================================================================== */

function handleParticipantApiError(status, result) {

    if (status === 409) {

        const message =
            result.message ||
            'User is already a participant in this booking.';

        showParticipantFormError(
            message
        );

        showAppNotification(
            message,
            'warning',
            'Duplicate Participant'
        );

        return;
    }

    if (
        status === 422 &&
        result.errors
    ) {

        const messages =
            Object.values(
                result.errors
            );

        const message =
            messages.join(' ');

        showParticipantFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'Validation Error'
        );

        return;
    }

    if (
        status === 422 &&
        result.message
    ) {

        showParticipantFormError(
            result.message
        );

        showAppNotification(
            result.message,
            'error',
            'Validation Error'
        );

        return;
    }

    if (status === 404) {

        const message =
            result.message ||
            'Booking or user could not be found.';

        showParticipantFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'Not Found'
        );

        return;
    }

    const message =
        result.message ||
        'Unable to process participant request.';

    showParticipantFormError(
        message
    );

    showAppNotification(
        message,
        'error',
        'Participant Error'
    );
}


/* ==========================================================================
   FORM MESSAGES
   ========================================================================== */

function showParticipantFormError(message) {

    const errorBox =
        document.getElementById(
            'participantFormError'
        );

    const errorText =
        document.getElementById(
            'participantFormErrorText'
        );

    const successBox =
        document.getElementById(
            'participantFormSuccess'
        );

    successBox?.classList.add(
        'd-none'
    );

    if (errorText) {
        errorText.textContent =
            message;
    }

    errorBox?.classList.remove(
        'd-none'
    );
}


function showParticipantFormSuccess(message) {

    const successBox =
        document.getElementById(
            'participantFormSuccess'
        );

    const successText =
        document.getElementById(
            'participantFormSuccessText'
        );

    const errorBox =
        document.getElementById(
            'participantFormError'
        );

    errorBox?.classList.add(
        'd-none'
    );

    if (successText) {
        successText.textContent =
            message;
    }

    successBox?.classList.remove(
        'd-none'
    );
}


function hideParticipantFormMessages() {

    document.getElementById(
        'participantFormError'
    )?.classList.add(
        'd-none'
    );

    document.getElementById(
        'participantFormSuccess'
    )?.classList.add(
        'd-none'
    );
}


/* ==========================================================================
   HELPERS
   ========================================================================== */

function formatBookingDate(date) {

    if (
        !date ||
        Number.isNaN(
            date.getTime()
        )
    ) {
        return '—';
    }

    return date.toLocaleDateString(
        'en-IN',
        {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }
    );
}


function formatBookingTime(date) {

    if (
        !date ||
        Number.isNaN(
            date.getTime()
        )
    ) {
        return '—';
    }

    return date.toLocaleTimeString(
        'en-IN',
        {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }
    );
}

})();
