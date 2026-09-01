/**
 * MeetSpace Enterprise Suite - Bookings Module JavaScript
 *
 * Handles:
 * - Booking listing and rendering
 * - Booking search and filtering
 * - New booking modal dialog
 * - Room and user dropdown population
 * - Booking form validation and creation
 * - API error handling and date/time formatting
 */

document.addEventListener('DOMContentLoaded', () => {

    // Bookings page
    if (document.getElementById('bookingsTableBody')) {
        loadBookings();
        setupBookingFilters();
        setupBookingModal();
    }
});


/* ==========================================================================
   BOOKINGS
   ========================================================================== */

let allBookings = [];


async function loadBookings() {

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

        const response =
            await fetch('/api/bookings');

        if (!response.ok) {
            throw new Error(
                `HTTP error: ${response.status}`
            );
        }

        const result =
            await response.json();

        if (result.status !== 'success') {
            throw new Error(
                'Bookings request failed.'
            );
        }

        allBookings =
            result.data || [];

        loading?.classList.add(
            'd-none'
        );

        renderBookings(
            allBookings
        );

    } catch (err) {

        console.error(
            'Unable to load bookings:',
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

        const startDate =
            new Date(
                String(
                    booking.start_time
                ).replace(' ', 'T')
            );

        const endDate =
            new Date(
                String(
                    booking.end_time
                ).replace(' ', 'T')
            );

        const dateText =
            formatBookingDate(
                startDate
            );

        const timeText =
            `${formatBookingTime(startDate)} - ${formatBookingTime(endDate)}`;

        const organizer =
            booking.organizer_name ||
            `User #${booking.user_id}`;

        const room =
            booking.room_name ||
            `Room #${booking.room_id}`;

        row.innerHTML = `

            <td>

                <div class="booking-title">
                    ${escapeHtml(
                        booking.title ||
                        'Untitled Meeting'
                    )}
                </div>

                ${
                    booking.description
                        ? `
                            <div class="booking-description">
                                ${escapeHtml(
                                    booking.description
                                )}
                            </div>
                        `
                        : ''
                }

            </td>

            <td>

                <div class="booking-room-name">
                    ${escapeHtml(room)}
                </div>

                ${
                    booking.room_code
                        ? `
                            <div class="booking-room-code">
                                ${escapeHtml(
                                    booking.room_code
                                )}
                            </div>
                        `
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

                <span class="booking-organizer">
                    ${escapeHtml(organizer)}
                </span>

            </td>

            <td>
                ${renderBookingStatus(
                    booking.status
                )}
            </td>

        `;

        tableBody.appendChild(row);
    });
}


/* ==========================================================================
   BOOKING FILTERS
   ========================================================================== */

function setupBookingFilters() {

    const searchInput =
        document.getElementById(
            'bookingSearch'
        );

    const statusFilter =
        document.getElementById(
            'statusFilter'
        );

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            applyBookingFilters
        );
    }

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            applyBookingFilters
        );
    }
}


function applyBookingFilters() {

    const searchInput =
        document.getElementById(
            'bookingSearch'
        );

    const statusFilter =
        document.getElementById(
            'statusFilter'
        );

    const searchTerm =
        searchInput
            ? searchInput.value
                .trim()
                .toLowerCase()
            : '';

    const selectedStatus =
        statusFilter
            ? statusFilter.value
                .toLowerCase()
            : '';

    const filteredBookings =
        allBookings.filter(
            (booking) => {

                const searchableText = [
                    booking.title,
                    booking.description,
                    booking.room_name,
                    booking.room_code,
                    booking.organizer_name,
                    booking.organizer_email
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                const matchesSearch =
                    !searchTerm ||
                    searchableText.includes(
                        searchTerm
                    );

                const matchesStatus =
                    !selectedStatus ||
                    String(
                        booking.status
                    ).toLowerCase() ===
                    selectedStatus;

                return (
                    matchesSearch &&
                    matchesStatus
                );
            }
        );

    renderBookings(
        filteredBookings
    );
}


/* ==========================================================================
   NEW BOOKING MODAL
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

    const closeBookingModal =
        document.getElementById(
            'closeBookingModal'
        );

    const cancelBookingBtn =
        document.getElementById(
            'cancelBookingBtn'
        );

    const form =
        document.getElementById(
            'newBookingForm'
        );

    if (
        !modal ||
        !newBookingBtn ||
        !form
    ) {
        return;
    }

    newBookingBtn.addEventListener(
        'click',
        async () => {

            resetBookingForm();

            openBookingModal();

            await Promise.all([
                loadBookingRooms(),
                loadBookingUsers()
            ]);
        }
    );

    closeBookingModal?.addEventListener(
        'click',
        closeBookingModalWindow
    );

    cancelBookingBtn?.addEventListener(
        'click',
        closeBookingModalWindow
    );

    modal.addEventListener(
        'click',
        (event) => {

            if (
                event.target === modal
            ) {
                closeBookingModalWindow();
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
                closeBookingModalWindow();
            }

            const confirmModal =
                document.getElementById(
                    'appConfirmModal'
                );

            if (
                event.key === 'Escape' &&
                confirmModal &&
                !confirmModal.classList.contains(
                    'd-none'
                )
            ) {
                hideAppConfirm();
            }
        }
    );

    form.addEventListener(
        'submit',
        handleNewBookingSubmit
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

    modal.classList.remove(
        'd-none'
    );

    document.body.classList.add(
        'booking-modal-open'
    );

    setTimeout(() => {

        document.getElementById(
            'bookingTitle'
        )?.focus();

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

    modal.classList.add(
        'd-none'
    );

    document.body.classList.remove(
        'booking-modal-open'
    );
}


function resetBookingForm() {

    const form =
        document.getElementById(
            'newBookingForm'
        );

    form?.reset();

    hideBookingFormMessages();

    const submitButton =
        document.getElementById(
            'submitBookingBtn'
        );

    if (submitButton) {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-calendar-check"></i>
            Create Booking
        `;
    }

    const roomSelect =
        document.getElementById(
            'bookingRoom'
        );

    if (roomSelect) {

        roomSelect.innerHTML =
            '<option value="">Loading rooms...</option>';
    }

    const userSelect =
        document.getElementById(
            'bookingUser'
        );

    if (userSelect) {

        userSelect.innerHTML =
            '<option value="">Loading users...</option>';
    }
}


/* ==========================================================================
   BOOKING ROOM LOADING
   ========================================================================== */

async function loadBookingRooms() {

    const select =
        document.getElementById(
            'bookingRoom'
        );

    if (!select) {
        return;
    }

    try {

        const response =
            await fetch('/api/rooms');

        if (!response.ok) {

            throw new Error(
                `HTTP error: ${response.status}`
            );
        }

        const result =
            await response.json();

        if (
            result.status !==
            'success'
        ) {

            throw new Error(
                'Rooms request failed.'
            );
        }

        const rooms =
            result.data || [];

        select.innerHTML = '';

        const defaultOption =
            document.createElement(
                'option'
            );

        defaultOption.value = '';

        defaultOption.textContent =
            'Select a room';

        select.appendChild(
            defaultOption
        );

        rooms
            .filter(
                (room) =>
                    String(
                        room.is_active
                    ) === '1'
            )
            .forEach(
                (room) => {

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        room.id;

                    option.textContent =
                        `${room.name} (${room.room_code})`;

                    select.appendChild(
                        option
                    );
                }
            );

        if (
            select.options.length === 1
        ) {

            select.innerHTML =
                '<option value="">No active rooms available</option>';
        }

    } catch (error) {

        console.error(
            'Unable to load rooms:',
            error
        );

        select.innerHTML =
            '<option value="">Unable to load rooms</option>';

        showBookingFormError(
            'Unable to load rooms. Please try again.'
        );
    }
}


/* ==========================================================================
   BOOKING USER LOADING
   ========================================================================== */

async function loadBookingUsers() {

    const select =
        document.getElementById(
            'bookingUser'
        );

    if (!select) {
        return;
    }

    try {

        const response =
            await fetch('/users');

        if (!response.ok) {

            throw new Error(
                `HTTP error: ${response.status}`
            );
        }

        const result =
            await response.json();

        if (
            result.status !==
            'success'
        ) {

            throw new Error(
                'Users request failed.'
            );
        }

        const users =
            result.data || [];

        select.innerHTML = '';

        const defaultOption =
            document.createElement(
                'option'
            );

        defaultOption.value = '';

        defaultOption.textContent =
            'Select organizer';

        select.appendChild(
            defaultOption
        );

        users
            .filter(
                (user) =>
                    String(
                        user.is_active
                    ) === '1'
            )
            .forEach(
                (user) => {

                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        user.id;

                    const fullName =
                        `${user.first_name || ''} ${user.last_name || ''}`
                            .trim();

                    option.textContent =
                        fullName ||
                        user.email ||
                        `User #${user.id}`;

                    select.appendChild(
                        option
                    );
                }
            );

        if (
            select.options.length === 1
        ) {

            select.innerHTML =
                '<option value="">No active users available</option>';
        }

    } catch (error) {

        console.error(
            'Unable to load users:',
            error
        );

        select.innerHTML =
            '<option value="">Unable to load users</option>';

        showBookingFormError(
            'Unable to load organizers. Please try again.'
        );
    }
}


/* ==========================================================================
   CREATE BOOKING
   ========================================================================== */

async function handleNewBookingSubmit(event) {

    event.preventDefault();

    const form =
        document.getElementById(
            'newBookingForm'
        );

    const submitButton =
        document.getElementById(
            'submitBookingBtn'
        );

    if (
        !form ||
        !submitButton
    ) {
        return;
    }

    hideBookingFormMessages();

    const formData =
        new FormData(form);

    const title =
        formData.get('title')
            ?.trim() || '';

    const roomId =
        formData.get('room_id');

    const userId =
        formData.get('user_id');

    const startTime =
        formData.get('start_time');

    const endTime =
        formData.get('end_time');

    const description =
        formData.get('description')
            ?.trim() || '';

    if (
        !title ||
        !roomId ||
        !userId ||
        !startTime ||
        !endTime
    ) {

        showBookingFormError(
            'Please fill in all required fields.'
        );

        return;
    }

    if (
        new Date(startTime) >=
        new Date(endTime)
    ) {

        showBookingFormError(
            'End time must be after start time.'
        );

        return;
    }

    const payload = {

        title: title,

        room_id:
            Number(roomId),

        user_id:
            Number(userId),

        start_time:
            formatDateTimeForApi(
                startTime
            ),

        end_time:
            formatDateTimeForApi(
                endTime
            ),

        description:
            description,

        status:
            'pending'
    };

    try {

        submitButton.disabled =
            true;

        submitButton.innerHTML = `
            <i class="bi bi-arrow-repeat spin"></i>
            Creating...
        `;

        const response =
            await fetch(
                '/bookings',
                {
                    method: 'POST',

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

            handleBookingApiError(
                response.status,
                result
            );

            return;
        }

        showBookingFormSuccess(
            result.message ||
            'Booking created successfully.'
        );

        showAppNotification(
            result.message ||
            'Booking created successfully.',
            'success',
            'Booking Created'
        );

        await loadBookings();

        setTimeout(() => {

            closeBookingModalWindow();

        }, 800);

    } catch (error) {

        console.error(
            'Unable to create booking:',
            error
        );

        showBookingFormError(
            'Unable to create booking. Please try again.'
        );

    } finally {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-calendar-check"></i>
            Create Booking
        `;
    }
}


/* ==========================================================================
   BOOKING API ERROR HANDLING
   ========================================================================== */

function handleBookingApiError(
    status,
    result
) {

    if (
        status === 409
    ) {

        showBookingFormError(
            result.message ||
            'Room is already booked during this time.'
        );

        showAppNotification(
            result.message ||
            'Room is already booked during this time.',
            'warning',
            'Booking Conflict'
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

        showBookingFormError(
            messages.join(' ')
        );

        return;
    }

    if (
        status === 404
    ) {

        showBookingFormError(
            result.message ||
            'The selected room or organizer could not be found.'
        );

        return;
    }

    showBookingFormError(
        result.message ||
        'Unable to create booking.'
    );
}


/* ==========================================================================
   BOOKING FORM MESSAGES
   ========================================================================== */

function showBookingFormError(message) {

    const errorBox =
        document.getElementById(
            'bookingFormError'
        );

    const errorText =
        document.getElementById(
            'bookingFormErrorText'
        );

    const successBox =
        document.getElementById(
            'bookingFormSuccess'
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


function showBookingFormSuccess(message) {

    const successBox =
        document.getElementById(
            'bookingFormSuccess'
        );

    const successText =
        document.getElementById(
            'bookingFormSuccessText'
        );

    const errorBox =
        document.getElementById(
            'bookingFormError'
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


function hideBookingFormMessages() {

    document.getElementById(
        'bookingFormError'
    )?.classList.add(
        'd-none'
    );

    document.getElementById(
        'bookingFormSuccess'
    )?.classList.add(
        'd-none'
    );
}


/* ==========================================================================
   BOOKING HELPERS
   ========================================================================== */

function formatDateTimeForApi(value) {

    if (!value) {
        return '';
    }

    return value.replace(
        'T',
        ' '
    ) + ':00';
}


function formatBookingDate(date) {

    if (
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


function renderBookingStatus(status) {

    const normalizedStatus =
        String(
            status || 'pending'
        ).toLowerCase();

    const label =
        normalizedStatus
            .charAt(0)
            .toUpperCase() +
        normalizedStatus.slice(1);

    return `
        <span
            class="booking-status booking-status-${escapeHtml(
                normalizedStatus
            )}"
        >
            ${escapeHtml(label)}
        </span>
    `;
}
