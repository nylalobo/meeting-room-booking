/**
 * MeetSpace Enterprise Suite - Bookings Module JavaScript
 *
 * Handles:
 * - Encapsulated within an IIFE to prevent global namespace pollution
 * - Concurrently loading bookings, rooms, and users via Promise.all
 * - Room and user dropdown population (preserving inactive entities on edit)
 * - Multi-criteria search and filtering (text search, room filter, status filter)
 * - Create and Edit modal workflows with datetime-local mapping
 * - Custom confirmation deletion dialog via showAppConfirm
 * - API response and error handling (404, 409 overlap conflict, 422 validation)
 * - HTML escaping for XSS prevention and app-level toast notifications
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        if (document.getElementById('bookingsTableBody')) {
            loadBookingsData();
            setupBookingFilters();
            setupBookingModal();
        }
    });


    /* ==========================================================================
       STATE
       ========================================================================== */

    let allBookings = [];
    let allRooms = [];
    let allUsers = [];
    let roomsMap = new Map();
    let usersMap = new Map();


    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadBookingsData() {

        const loading =
            document.getElementById(
                'bookingsLoading'
            );

        const error =
            document.getElementById(
                'bookingsError'
            );

        const empty =
            document.getElementById(
                'bookingsEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'bookingsTableWrapper'
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
                bookingsRes,
                roomsRes,
                usersRes
            ] = await Promise.all([
                fetch('/api/bookings'),
                fetch('/api/rooms'),
                fetch('/api/users')
            ]);

            if (
                !bookingsRes.ok ||
                !roomsRes.ok ||
                !usersRes.ok
            ) {

                throw new Error(
                    'Failed to load booking dependencies.'
                );
            }

            const [
                bookingsResult,
                roomsResult,
                usersResult
            ] = await Promise.all([
                bookingsRes.json(),
                roomsRes.json(),
                usersRes.json()
            ]);

            allBookings =
                bookingsResult.data || [];

            allRooms =
                roomsResult.data || [];

            allUsers =
                usersResult.data || [];

            roomsMap = new Map(
                allRooms.map((r) => [
                    Number(r.id),
                    r
                ])
            );

            usersMap = new Map(
                allUsers.map((u) => [
                    Number(u.id),
                    u
                ])
            );

            populateFilterDropdowns();
            populateModalDropdowns();

            loading?.classList.add(
                'd-none'
            );

            renderBookings(
                allBookings
            );

        } catch (err) {

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
       DROPDOWN POPULATION
       ========================================================================== */

    function populateFilterDropdowns() {

        const roomFilter =
            document.getElementById(
                'bookingRoomFilter'
            );

        if (!roomFilter) {
            return;
        }

        const currentVal =
            roomFilter.value;

        roomFilter.innerHTML =
            '<option value="">All Rooms</option>';

        allRooms
            .filter((room) => String(room.is_active) === '1')
            .forEach((room) => {

                const opt =
                    document.createElement('option');

                opt.value =
                    String(room.id);

                opt.textContent =
                    room.room_code
                        ? `${room.name} (${room.room_code})`
                        : room.name;

                roomFilter.appendChild(opt);
            });

        if (currentVal) {
            roomFilter.value = currentVal;
        }
    }


    function populateModalDropdowns(selectedRoomId = null, selectedUserId = null) {

        const roomSelect =
            document.getElementById(
                'bookingRoom'
            );

        const userSelect =
            document.getElementById(
                'bookingUser'
            );

        if (roomSelect) {

            roomSelect.innerHTML =
                '<option value="">Select a room...</option>';

            allRooms.forEach((room) => {

                const isActive =
                    String(room.is_active) === '1';

                const isCurrent =
                    selectedRoomId !== null &&
                    Number(room.id) === Number(selectedRoomId);

                if (isActive || isCurrent) {

                    const opt =
                        document.createElement('option');

                    opt.value =
                        String(room.id);

                    const label =
                        room.room_code
                            ? `${room.name} (${room.room_code})`
                            : room.name;

                    opt.textContent =
                        isActive
                            ? label
                            : `${label} (Inactive)`;

                    roomSelect.appendChild(opt);
                }
            });

            if (selectedRoomId) {
                roomSelect.value = String(selectedRoomId);
            }
        }

        if (userSelect) {

            userSelect.innerHTML =
                '<option value="">Select organizer...</option>';

            allUsers.forEach((user) => {

                const isActive =
                    String(user.is_active) === '1';

                const isCurrent =
                    selectedUserId !== null &&
                    Number(user.id) === Number(selectedUserId);

                if (isActive || isCurrent) {

                    const opt =
                        document.createElement('option');

                    opt.value =
                        String(user.id);

                    const fullName =
                        `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
                        user.email ||
                        `User #${user.id}`;

                    opt.textContent =
                        isActive
                            ? fullName
                            : `${fullName} (Inactive)`;

                    userSelect.appendChild(opt);
                }
            });

            if (selectedUserId) {
                userSelect.value = String(selectedUserId);
            }
        }
    }


    /* ==========================================================================
       RENDERING
       ========================================================================== */

    function renderBookings(bookings) {

        const tableBody =
            document.getElementById(
                'bookingsTableBody'
            );

        const empty =
            document.getElementById(
                'bookingsEmpty'
            );

        const tableWrapper =
            document.getElementById(
                'bookingsTableWrapper'
            );

        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = '';

        if (!bookings.length) {

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

        bookings.forEach((booking) => {

            const row =
                document.createElement('tr');

            const roomData =
                roomsMap.get(Number(booking.room_id));

            const userData =
                usersMap.get(Number(booking.user_id));

            const roomName =
                booking.room_name ||
                roomData?.name ||
                `Room #${booking.room_id}`;

            const roomCode =
                booking.room_code ||
                roomData?.room_code ||
                '';

            const organizerName =
                booking.organizer_name ||
                (userData
                    ? `${userData.first_name || ''} ${userData.last_name || ''}`.trim()
                    : `User #${booking.user_id}`);

            const organizerEmail =
                booking.organizer_email ||
                userData?.email ||
                '';

            const dateText =
                formatDateForDisplay(booking.start_time);

            const timeText =
                formatTimeRangeForDisplay(
                    booking.start_time,
                    booking.end_time
                );

            const normalizedStatus =
                String(booking.status || 'pending').toLowerCase();

            const statusLabel =
                normalizedStatus.charAt(0).toUpperCase() +
                normalizedStatus.slice(1);

            row.innerHTML = `

                <td>
                    <div class="booking-title">
                        ${escapeHtml(booking.title || 'Untitled Meeting')}
                    </div>
                    ${
                        booking.description
                            ? `<div class="booking-description">${escapeHtml(booking.description)}</div>`
                            : ''
                    }
                </td>

                <td>
                    <div class="booking-room-name">
                        ${escapeHtml(roomName)}
                    </div>
                    ${
                        roomCode
                            ? `<div class="booking-room-code">${escapeHtml(roomCode)}</div>`
                            : ''
                    }
                </td>

                <td>
                    <span class="booking-date">
                        ${escapeHtml(dateText)}
                    </span>
                </td>

                <td>
                    <span class="booking-time">
                        ${escapeHtml(timeText)}
                    </span>
                </td>

                <td>
                    <div>
                        ${escapeHtml(organizerName)}
                    </div>
                    ${
                        organizerEmail
                            ? `<div class="booking-description">${escapeHtml(organizerEmail)}</div>`
                            : ''
                    }
                </td>

                <td>
                    <span class="booking-status booking-status-${escapeHtml(normalizedStatus)}">
                        ${escapeHtml(statusLabel)}
                    </span>
                </td>

                <td>
                    <div class="booking-actions">
                        <button
                            type="button"
                            class="booking-action-btn booking-edit-btn"
                            data-booking-id="${escapeHtml(booking.id)}"
                            title="Edit booking"
                            aria-label="Edit booking"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button
                            type="button"
                            class="booking-action-btn booking-delete-btn"
                            data-booking-id="${escapeHtml(booking.id)}"
                            title="Delete booking"
                            aria-label="Delete booking"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>

            `;

            tableBody.appendChild(row);
        });

        setupBookingActionButtons();
    }


    /* ==========================================================================
       FILTERS
       ========================================================================== */

    function setupBookingFilters() {

        const searchInput =
            document.getElementById(
                'bookingSearch'
            );

        const roomFilter =
            document.getElementById(
                'bookingRoomFilter'
            );

        const statusFilter =
            document.getElementById(
                'statusFilter'
            );

        searchInput?.addEventListener(
            'input',
            applyBookingFilters
        );

        roomFilter?.addEventListener(
            'change',
            applyBookingFilters
        );

        statusFilter?.addEventListener(
            'change',
            applyBookingFilters
        );
    }


    function applyBookingFilters() {

        const searchInput =
            document.getElementById(
                'bookingSearch'
            );

        const roomFilter =
            document.getElementById(
                'bookingRoomFilter'
            );

        const statusFilter =
            document.getElementById(
                'statusFilter'
            );

        const searchTerm =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';

        const selectedRoom =
            roomFilter
                ? roomFilter.value
                : '';

        const selectedStatus =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : '';

        const filtered =
            allBookings.filter((booking) => {

                const title =
                    String(booking.title || '').toLowerCase();

                const desc =
                    String(booking.description || '').toLowerCase();

                const roomName =
                    String(booking.room_name || '').toLowerCase();

                const roomCode =
                    String(booking.room_code || '').toLowerCase();

                const organizerName =
                    String(booking.organizer_name || '').toLowerCase();

                const matchesSearch =
                    !searchTerm ||
                    title.includes(searchTerm) ||
                    desc.includes(searchTerm) ||
                    roomName.includes(searchTerm) ||
                    roomCode.includes(searchTerm) ||
                    organizerName.includes(searchTerm);

                const matchesRoom =
                    !selectedRoom ||
                    String(booking.room_id) === selectedRoom;

                const matchesStatus =
                    !selectedStatus ||
                    String(booking.status || '').toLowerCase() === selectedStatus;

                return (
                    matchesSearch &&
                    matchesRoom &&
                    matchesStatus
                );
            });

        renderBookings(filtered);
    }


    /* ==========================================================================
       MODAL SETUP
       ========================================================================== */

    function setupBookingModal() {

        const modal =
            document.getElementById(
                'bookingModal'
            );

        const newBookingBtn =
            document.getElementById(
                'newBookingBtn'
            );

        const closeBtn =
            document.getElementById(
                'closeBookingModal'
            );

        const cancelBtn =
            document.getElementById(
                'cancelBookingBtn'
            );

        const form =
            document.getElementById('bookingForm') ||
            document.getElementById('newBookingForm');

        if (!modal || !newBookingBtn || !form) {
            return;
        }

        newBookingBtn.addEventListener(
            'click',
            () => {
                resetBookingForm();
                openBookingModal();
            }
        );

        closeBtn?.addEventListener(
            'click',
            closeBookingModalWindow
        );

        cancelBtn?.addEventListener(
            'click',
            closeBookingModalWindow
        );

        modal.addEventListener(
            'click',
            (event) => {
                if (event.target === modal) {
                    closeBookingModalWindow();
                }
            }
        );

        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key === 'Escape' &&
                    !modal.classList.contains('d-none')
                ) {
                    closeBookingModalWindow();
                }
            }
        );

        form.addEventListener(
            'submit',
            handleBookingFormSubmit
        );
    }


    function openBookingModal() {

        const modal =
            document.getElementById(
                'bookingModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        setTimeout(() => {
            document.getElementById('bookingTitle')?.focus();
        }, 50);
    }


    function closeBookingModalWindow() {

        const modal =
            document.getElementById(
                'bookingModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
    }


    function resetBookingForm() {

        const form =
            document.getElementById('bookingForm') ||
            document.getElementById('newBookingForm');

        form?.reset();

        const bookingId =
            document.getElementById('bookingId');

        if (bookingId) {
            bookingId.value = '';
        }

        const modalTitle =
            document.getElementById('bookingModalTitle');

        if (modalTitle) {
            modalTitle.textContent = 'New Booking';
        }

        const modalSubtitle =
            document.getElementById('bookingModalSubtitle');

        if (modalSubtitle) {
            modalSubtitle.textContent =
                'Create a new meeting room booking.';
        }

        const statusSelect =
            document.getElementById('bookingStatus');

        if (statusSelect) {
            statusSelect.value = 'pending';
        }

        const submitButton =
            document.getElementById('submitBookingBtn');

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = `
                <i class="bi bi-calendar-check"></i>
                Create Booking
            `;
        }

        populateModalDropdowns();
        hideBookingFormMessages();
    }


    /* ==========================================================================
       ACTION BUTTONS (EDIT / DELETE)
       ========================================================================== */

    function setupBookingActionButtons() {

        const editButtons =
            document.querySelectorAll(
                '.booking-edit-btn'
            );

        const deleteButtons =
            document.querySelectorAll(
                '.booking-delete-btn'
            );

        editButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = button.dataset.bookingId;
                    editBooking(bookingId);
                }
            );
        });

        deleteButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = button.dataset.bookingId;
                    deleteBooking(bookingId);
                }
            );
        });
    }


    function findBookingById(id) {

        return allBookings.find(
            (b) => String(b.id) === String(id)
        );
    }


    /* ==========================================================================
       EDIT BOOKING
       ========================================================================== */

    function editBooking(bookingId) {

        const booking = findBookingById(bookingId);

        if (!booking) {
            showAppNotification(
                'Unable to find the selected booking.',
                'error',
                'Booking Not Found'
            );
            return;
        }

        const bookingIdInput =
            document.getElementById('bookingId');

        const titleInput =
            document.getElementById('bookingTitle');

        const roomSelect =
            document.getElementById('bookingRoom');

        const userSelect =
            document.getElementById('bookingUser');

        const startInput =
            document.getElementById('bookingStart');

        const endInput =
            document.getElementById('bookingEnd');

        const statusSelect =
            document.getElementById('bookingStatus');

        const descriptionInput =
            document.getElementById('bookingDescription');

        if (bookingIdInput) {
            bookingIdInput.value = booking.id;
        }

        if (titleInput) {
            titleInput.value = booking.title || '';
        }

        populateModalDropdowns(booking.room_id, booking.user_id);

        if (roomSelect) {
            roomSelect.value = String(booking.room_id);
        }

        if (userSelect) {
            userSelect.value = String(booking.user_id);
        }

        if (startInput) {
            startInput.value = formatDateTimeForInput(booking.start_time);
        }

        if (endInput) {
            endInput.value = formatDateTimeForInput(booking.end_time);
        }

        if (statusSelect) {
            statusSelect.value = String(booking.status || 'pending').toLowerCase();
        }

        if (descriptionInput) {
            descriptionInput.value = booking.description || '';
        }

        const modalTitle =
            document.getElementById('bookingModalTitle');

        if (modalTitle) {
            modalTitle.textContent = 'Edit Booking';
        }

        const modalSubtitle =
            document.getElementById('bookingModalSubtitle');

        if (modalSubtitle) {
            modalSubtitle.textContent =
                'Update meeting room booking details.';
        }

        const submitButton =
            document.getElementById('submitBookingBtn');

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = `
                <i class="bi bi-check-lg"></i>
                Save Changes
            `;
        }

        hideBookingFormMessages();
        openBookingModal();
    }


    /* ==========================================================================
       CREATE / UPDATE SUBMISSION
       ========================================================================== */

    async function handleBookingFormSubmit(event) {

        event.preventDefault();

        const form =
            document.getElementById('bookingForm') ||
            document.getElementById('newBookingForm');

        const submitButton =
            document.getElementById('submitBookingBtn');

        if (!form || !submitButton) {
            return;
        }

        hideBookingFormMessages();

        const bookingId =
            document.getElementById('bookingId')?.value.trim() || '';

        const title =
            document.getElementById('bookingTitle')?.value.trim() || '';

        const roomId =
            document.getElementById('bookingRoom')?.value;

        const userId =
            document.getElementById('bookingUser')?.value;

        const startTimeLocal =
            document.getElementById('bookingStart')?.value;

        const endTimeLocal =
            document.getElementById('bookingEnd')?.value;

        const status =
            document.getElementById('bookingStatus')?.value || 'pending';

        const description =
            document.getElementById('bookingDescription')?.value.trim() || '';

        const isEditing = Boolean(bookingId);

        if (!title || !roomId || !userId || !startTimeLocal || !endTimeLocal) {
            showBookingFormError('Please fill in all required fields.');
            return;
        }

        const startTimeApi = formatDateTimeForApi(startTimeLocal);
        const endTimeApi = formatDateTimeForApi(endTimeLocal);

        if (new Date(startTimeLocal) >= new Date(endTimeLocal)) {
            showBookingFormError('End time must be after start time.');
            return;
        }

        const payload = {
            room_id: Number(roomId),
            user_id: Number(userId),
            title: title,
            description: description || null,
            start_time: startTimeApi,
            end_time: endTimeApi,
            status: status
        };

        const url = isEditing
            ? `/api/bookings/${bookingId}`
            : '/api/bookings';

        const method = isEditing ? 'PUT' : 'POST';

        try {

            submitButton.disabled = true;
            submitButton.innerHTML = `
                <i class="bi bi-arrow-repeat spin"></i>
                ${isEditing ? 'Updating...' : 'Creating...'}
            `;

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                handleBookingApiError(response.status, result);
                return;
            }

            const successMessage =
                result.message ||
                (isEditing
                    ? 'Booking updated successfully.'
                    : 'Booking created successfully.');

            showBookingFormSuccess(successMessage);

            showAppNotification(
                successMessage,
                'success',
                isEditing ? 'Booking Updated' : 'Booking Created'
            );

            await loadBookingsData();

            setTimeout(() => {
                closeBookingModalWindow();
            }, 800);

        } catch (error) {

            showBookingFormError(
                'Unable to save booking. Please try again.'
            );

            showAppNotification(
                'Unable to save booking. Please try again.',
                'error',
                'Save Failed'
            );

        } finally {

            submitButton.disabled = false;
            submitButton.innerHTML = `
                <i class="bi bi-${isEditing ? 'check-lg' : 'calendar-check'}"></i>
                ${isEditing ? 'Save Changes' : 'Create Booking'}
            `;
        }
    }


    /* ==========================================================================
       DELETE BOOKING
       ========================================================================== */

    function deleteBooking(bookingId) {

        const booking = findBookingById(bookingId);

        if (!booking) {
            showAppNotification(
                'Unable to find the selected booking.',
                'error',
                'Booking Not Found'
            );
            return;
        }

        const dateText = formatDateForDisplay(booking.start_time);
        const titleText = booking.title || 'this meeting';

        showAppConfirm(
            `Are you sure you want to delete "${titleText}" (${dateText})?`,
            async () => {
                await performBookingDelete(bookingId, titleText);
            },
            'Delete Booking',
            'Delete Booking'
        );
    }


    async function performBookingDelete(bookingId, titleText) {

        try {

            const response = await fetch(`/api/bookings/${bookingId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Unable to delete booking.');
            }

            await loadBookingsData();

            showAppNotification(
                result.message || `"${titleText}" has been deleted successfully.`,
                'success',
                'Booking Deleted'
            );

        } catch (error) {

            showAppNotification(
                error.message || 'Unable to delete booking. Please try again.',
                'error',
                'Delete Failed'
            );
        }
    }


    /* ==========================================================================
       API ERROR HANDLING
       ========================================================================== */

    function handleBookingApiError(status, result) {

        if (status === 409) {

            const message =
                result.message ||
                'Room is already booked during this time.';

            showBookingFormError(message);

            showAppNotification(
                message,
                'warning',
                'Booking Conflict'
            );

            return;
        }

        if (status === 422 && result.errors) {

            const messages =
                Object.values(result.errors);

            const combinedMessage =
                messages.join(' ');

            showBookingFormError(combinedMessage);

            showAppNotification(
                combinedMessage,
                'error',
                'Validation Error'
            );

            return;
        }

        if (status === 404) {

            const message =
                result.message ||
                'The selected booking, room, or user could not be found.';

            showBookingFormError(message);

            showAppNotification(
                message,
                'error',
                'Not Found'
            );

            return;
        }

        const fallback =
            result.message || 'Unable to process booking request.';

        showBookingFormError(fallback);

        showAppNotification(
            fallback,
            'error',
            'Booking Error'
        );
    }


    /* ==========================================================================
       FORM MESSAGES
       ========================================================================== */

    function showBookingFormError(message) {

        const errorBox =
            document.getElementById('bookingFormError');

        const errorText =
            document.getElementById('bookingFormErrorText');

        const successBox =
            document.getElementById('bookingFormSuccess');

        successBox?.classList.add('d-none');

        if (errorText) {
            errorText.textContent = message;
        }

        errorBox?.classList.remove('d-none');
    }


    function showBookingFormSuccess(message) {

        const successBox =
            document.getElementById('bookingFormSuccess');

        const successText =
            document.getElementById('bookingFormSuccessText');

        const errorBox =
            document.getElementById('bookingFormError');

        errorBox?.classList.add('d-none');

        if (successText) {
            successText.textContent = message;
        }

        successBox?.classList.remove('d-none');
    }


    function hideBookingFormMessages() {

        document.getElementById('bookingFormError')?.classList.add('d-none');
        document.getElementById('bookingFormSuccess')?.classList.add('d-none');
    }


    /* ==========================================================================
       DATE / TIME CONVERSION HELPERS
       (Preserves local meeting-room time without timezone displacement)
       ========================================================================== */

    function formatDateTimeForInput(apiDateTime) {

        if (!apiDateTime) {
            return '';
        }

        return String(apiDateTime)
            .replace(' ', 'T')
            .slice(0, 16);
    }


    function formatDateTimeForApi(localDateTime) {

        if (!localDateTime) {
            return '';
        }

        const cleaned = String(localDateTime).replace('T', ' ');

        if (cleaned.length === 16) {
            return `${cleaned}:00`;
        }

        return cleaned;
    }


    function parseDateTimeComponents(dateTimeStr) {

        if (!dateTimeStr) {
            return null;
        }

        const normalized = String(dateTimeStr).replace('T', ' ');
        const [datePart, timePart] = normalized.split(' ');

        if (!datePart || !timePart) {
            return null;
        }

        const [year, month, day] = datePart.split('-').map(Number);
        const [hours, minutes] = timePart.split(':').map(Number);

        if (!year || !month || !day) {
            return null;
        }

        return { year, month, day, hours: hours || 0, minutes: minutes || 0 };
    }


    function formatDateForDisplay(dateTimeStr) {

        const comp = parseDateTimeComponents(dateTimeStr);

        if (!comp) {
            return '—';
        }

        const months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        const monthName = months[comp.month - 1] || '';
        const dayStr = String(comp.day).padStart(2, '0');

        return `${dayStr} ${monthName} ${comp.year}`;
    }


    function formatTimeForDisplay(hours, minutes) {

        const h = Number(hours);
        const m = String(minutes).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        const displayHours = h % 12 === 0 ? 12 : h % 12;

        return `${String(displayHours).padStart(2, '0')}:${m} ${ampm}`;
    }


    function formatTimeRangeForDisplay(startStr, endStr) {

        const startComp = parseDateTimeComponents(startStr);
        const endComp = parseDateTimeComponents(endStr);

        if (!startComp || !endComp) {
            return '—';
        }

        const startTime = formatTimeForDisplay(startComp.hours, startComp.minutes);
        const endTime = formatTimeForDisplay(endComp.hours, endComp.minutes);

        return `${startTime} - ${endTime}`;
    }

})();
