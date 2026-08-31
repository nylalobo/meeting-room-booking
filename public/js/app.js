/**
 * MeetSpace Enterprise Suite - Application JavaScript
 *
 * Updated:
 * - Custom in-page notifications
 * - Custom delete confirmation modal
 * - No browser alert()
 * - No browser confirm()
 * - Room CRUD
 * - Booking CRUD
 * - Dashboard statistics
 */

document.addEventListener('DOMContentLoaded', () => {

    // Inject notification / confirmation UI
    initializeAppNotifications();

    // Global search input
    const searchInput = document.querySelector('.search-input');

    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();

                console.log(
                    'Search query:',
                    searchInput.value.trim()
                );
            }
        });
    }

    // Dashboard
    if (document.getElementById('total-rooms')) {
        loadDashboardStats();
    }

    // Bookings page
    if (document.getElementById('bookingsTableBody')) {
        loadBookings();
        setupBookingFilters();
        setupBookingModal();
    }

    // Rooms page
    if (document.getElementById('roomsTableBody')) {
        loadRooms();
        setupRoomFilters();
        setupRoomModal();
    }

    console.log(
        'MeetSpace Enterprise Suite initialized.'
    );
});


/* ==========================================================================
   GLOBAL IN-PAGE NOTIFICATIONS
   ========================================================================== */

/**
 * Creates the notification and confirmation containers dynamically.
 * No HTML changes are required.
 */
function initializeAppNotifications() {

    // Notification container
    if (!document.getElementById('appNotificationContainer')) {

        const notificationContainer =
            document.createElement('div');

        notificationContainer.id =
            'appNotificationContainer';

        notificationContainer.innerHTML = `
            <div
                id="appNotification"
                class="app-notification"
                role="alert"
                aria-live="polite"
            >
                <div class="app-notification-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <div class="app-notification-content">
                    <div
                        id="appNotificationTitle"
                        class="app-notification-title"
                    >
                        Success
                    </div>

                    <div
                        id="appNotificationMessage"
                        class="app-notification-message"
                    >
                    </div>
                </div>

                <button
                    type="button"
                    id="appNotificationClose"
                    class="app-notification-close"
                    aria-label="Close notification"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;

        document.body.appendChild(
            notificationContainer
        );

        const closeButton =
            document.getElementById(
                'appNotificationClose'
            );

        if (closeButton) {
            closeButton.addEventListener(
                'click',
                hideAppNotification
            );
        }
    }

    // Confirmation modal
    if (!document.getElementById('appConfirmModal')) {

        const confirmModal =
            document.createElement('div');

        confirmModal.id =
            'appConfirmModal';

        confirmModal.className =
            'app-confirm-overlay d-none';

        confirmModal.innerHTML = `
            <div
                class="app-confirm-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="appConfirmTitle"
            >

                <div class="app-confirm-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>

                <div class="app-confirm-content">

                    <h3 id="appConfirmTitle">
                        Confirm Action
                    </h3>

                    <p id="appConfirmMessage">
                        Are you sure?
                    </p>

                </div>

                <div class="app-confirm-actions">

                    <button
                        type="button"
                        id="appConfirmCancel"
                        class="app-confirm-cancel"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        id="appConfirmProceed"
                        class="app-confirm-proceed"
                    >
                        Delete Room
                    </button>

                </div>

            </div>
        `;

        document.body.appendChild(
            confirmModal
        );

        const cancelButton =
            document.getElementById(
                'appConfirmCancel'
            );

        if (cancelButton) {
            cancelButton.addEventListener(
                'click',
                hideAppConfirm
            );
        }

        confirmModal.addEventListener(
            'click',
            (event) => {

                if (
                    event.target ===
                    confirmModal
                ) {
                    hideAppConfirm();
                }
            }
        );
    }

    // Inject styles
    injectAppNotificationStyles();
}


/**
 * Injects CSS for notifications and confirmation dialog.
 */
function injectAppNotificationStyles() {

    if (
        document.getElementById(
            'appNotificationStyles'
        )
    ) {
        return;
    }

    const style =
        document.createElement('style');

    style.id =
        'appNotificationStyles';

    style.textContent = `

        /* ==============================================================
           NOTIFICATION
           ============================================================== */

        #appNotificationContainer {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            pointer-events: none;
        }

        .app-notification {
            min-width: 340px;
            max-width: 460px;

            display: flex;
            align-items: flex-start;
            gap: 14px;

            padding: 16px 18px;

            background: #111827;
            border: 1px solid rgba(255,255,255,0.12);

            border-radius: 12px;

            box-shadow:
                0 20px 50px rgba(0,0,0,0.35);

            transform: translateX(120%);
            opacity: 0;

            transition:
                transform 0.3s ease,
                opacity 0.3s ease;

            pointer-events: auto;
        }

        .app-notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .app-notification.success {
            border-left: 4px solid #22c55e;
        }

        .app-notification.error {
            border-left: 4px solid #ef4444;
        }

        .app-notification.warning {
            border-left: 4px solid #f59e0b;
        }

        .app-notification.info {
            border-left: 4px solid #3b82f6;
        }

        .app-notification-icon {
            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            font-size: 20px;
        }

        .app-notification.success
        .app-notification-icon {
            color: #22c55e;
        }

        .app-notification.error
        .app-notification-icon {
            color: #ef4444;
        }

        .app-notification.warning
        .app-notification-icon {
            color: #f59e0b;
        }

        .app-notification.info
        .app-notification-icon {
            color: #3b82f6;
        }

        .app-notification-content {
            flex: 1;
            min-width: 0;
        }

        .app-notification-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .app-notification-message {
            color: #aab2d5;
            font-size: 13px;
            line-height: 1.5;
        }

        .app-notification-close {
            border: 0;
            background: transparent;

            color: #7f89ad;

            cursor: pointer;

            font-size: 14px;

            padding: 4px;

            transition:
                color 0.2s ease;
        }

        .app-notification-close:hover {
            color: #ffffff;
        }


        /* ==============================================================
           CONFIRMATION MODAL
           ============================================================== */

        .app-confirm-overlay {
            position: fixed;

            inset: 0;

            z-index: 99998;

            display: flex;

            align-items: center;
            justify-content: center;

            padding: 24px;

            background:
                rgba(3, 7, 18, 0.72);

            backdrop-filter: blur(4px);
        }

        .app-confirm-modal {
            width: min(440px, 100%);

            background: #111827;

            border:
                1px solid rgba(255,255,255,0.10);

            border-radius: 16px;

            padding: 28px;

            box-shadow:
                0 30px 80px rgba(0,0,0,0.50);

            animation:
                appConfirmAppear 0.2s ease;
        }

        @keyframes appConfirmAppear {

            from {
                opacity: 0;
                transform: translateY(12px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }

        .app-confirm-icon {
            width: 52px;
            height: 52px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin-bottom: 18px;

            border-radius: 50%;

            background:
                rgba(239, 68, 68, 0.12);

            color: #ef4444;

            font-size: 23px;
        }

        .app-confirm-content h3 {
            margin: 0 0 8px;

            color: #ffffff;

            font-size: 20px;

            font-weight: 700;
        }

        .app-confirm-content p {
            margin: 0;

            color: #9ca7c7;

            font-size: 14px;

            line-height: 1.6;
        }

        .app-confirm-actions {
            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 26px;
        }

        .app-confirm-actions button {
            border: 0;

            border-radius: 8px;

            padding: 10px 18px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition:
                transform 0.15s ease,
                opacity 0.15s ease;
        }

        .app-confirm-actions button:hover {
            transform: translateY(-1px);
        }

        .app-confirm-cancel {
            background: #252b3b;

            color: #d7dcef;
        }

        .app-confirm-cancel:hover {
            background: #30374a;
        }

        .app-confirm-proceed {
            background: #dc2626;

            color: #ffffff;
        }

        .app-confirm-proceed:hover {
            background: #b91c1c;
        }


        /* ==============================================================
           MOBILE
           ============================================================== */

        @media (max-width: 600px) {

            #appNotificationContainer {
                top: 16px;
                left: 16px;
                right: 16px;
            }

            .app-notification {
                min-width: 0;
                width: 100%;
            }

            .app-confirm-modal {
                padding: 22px;
            }

        }

    `;

    document.head.appendChild(style);
}


/**
 * Show an in-page notification.
 */
function showAppNotification(
    message,
    type = 'success',
    title = null
) {

    const notification =
        document.getElementById(
            'appNotification'
        );

    const titleElement =
        document.getElementById(
            'appNotificationTitle'
        );

    const messageElement =
        document.getElementById(
            'appNotificationMessage'
        );

    const icon =
        notification?.querySelector(
            '.app-notification-icon i'
        );

    if (
        !notification ||
        !titleElement ||
        !messageElement
    ) {
        return;
    }

    clearTimeout(
        window.appNotificationTimer
    );

    notification.classList.remove(
        'success',
        'error',
        'warning',
        'info',
        'show'
    );

    notification.classList.add(type);

    if (!title) {

        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };

        title =
            titles[type] ||
            'Notification';
    }

    titleElement.textContent =
        title;

    messageElement.textContent =
        message;

    if (icon) {

        const icons = {
            success:
                'bi bi-check-circle-fill',

            error:
                'bi bi-x-circle-fill',

            warning:
                'bi bi-exclamation-triangle-fill',

            info:
                'bi bi-info-circle-fill'
        };

        icon.className =
            icons[type] ||
            icons.info;
    }

    requestAnimationFrame(() => {

        notification.classList.add(
            'show'
        );

    });

    window.appNotificationTimer =
        setTimeout(() => {

            hideAppNotification();

        }, 4000);
}


/**
 * Hide notification.
 */
function hideAppNotification() {

    const notification =
        document.getElementById(
            'appNotification'
        );

    if (!notification) {
        return;
    }

    notification.classList.remove(
        'show'
    );
}


/**
 * Show custom confirmation dialog.
 */
function showAppConfirm(
    message,
    onConfirm,
    title = 'Confirm Action',
    confirmText = 'Delete Room'
) {

    const modal =
        document.getElementById(
            'appConfirmModal'
        );

    const titleElement =
        document.getElementById(
            'appConfirmTitle'
        );

    const messageElement =
        document.getElementById(
            'appConfirmMessage'
        );

    const confirmButton =
        document.getElementById(
            'appConfirmProceed'
        );

    if (
        !modal ||
        !titleElement ||
        !messageElement ||
        !confirmButton
    ) {
        return;
    }

    titleElement.textContent =
        title;

    messageElement.textContent =
        message;

    confirmButton.textContent =
        confirmText;

    modal.classList.remove(
        'd-none'
    );

    // Remove previous handler
    confirmButton.onclick = null;

    confirmButton.onclick = async () => {

        hideAppConfirm();

        if (typeof onConfirm === 'function') {
            await onConfirm();
        }
    };

    setTimeout(() => {
        confirmButton.focus();
    }, 50);
}


/**
 * Hide custom confirmation dialog.
 */
function hideAppConfirm() {

    const modal =
        document.getElementById(
            'appConfirmModal'
        );

    if (!modal) {
        return;
    }

    modal.classList.add(
        'd-none'
    );
}


/* ==========================================================================
   DASHBOARD
   ========================================================================== */

async function loadDashboardStats() {

    try {

        const response =
            await fetch('/dashboard/stats');

        if (!response.ok) {
            throw new Error(
                `HTTP error: ${response.status}`
            );
        }

        const result =
            await response.json();

        if (result.status !== 'success') {
            throw new Error(
                'Dashboard statistics request failed.'
            );
        }

        const stats =
            result.data;

        document.getElementById(
            'total-rooms'
        ).textContent =
            stats.total_rooms;

        document.getElementById(
            'total-bookings'
        ).textContent =
            stats.total_bookings;

        document.getElementById(
            'pending-requests'
        ).textContent =
            stats.pending_requests;

        document.getElementById(
            'active-users'
        ).textContent =
            stats.active_users;

    } catch (error) {

        console.error(
            'Unable to load dashboard statistics:',
            error
        );

    }
}


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


/* ==========================================================================
   ROOMS
   ========================================================================== */

let allRooms = [];


async function loadRooms() {

    const loading =
        document.getElementById(
            'roomsLoading'
        );

    const error =
        document.getElementById(
            'roomsError'
        );

    const empty =
        document.getElementById(
            'roomsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'roomsTableWrapper'
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
            await fetch(
                '/api/rooms'
            );

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

        allRooms =
            result.data || [];

        loading?.classList.add(
            'd-none'
        );

        renderRooms(
            allRooms
        );

    } catch (err) {

        console.error(
            'Unable to load rooms:',
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


function renderRooms(rooms) {

    const tableBody =
        document.getElementById(
            'roomsTableBody'
        );

    const empty =
        document.getElementById(
            'roomsEmpty'
        );

    const tableWrapper =
        document.getElementById(
            'roomsTableWrapper'
        );

    if (!tableBody) {
        return;
    }

    tableBody.innerHTML = '';

    if (!rooms.length) {

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

    rooms.forEach((room) => {

        const row =
            document.createElement(
                'tr'
            );

        const isActive =
            String(
                room.is_active
            ) === '1';

        const status =
            isActive
                ? 'Active'
                : 'Inactive';

        const statusClass =
            isActive
                ? 'active'
                : 'inactive';

        row.innerHTML = `

            <td>

                <div class="booking-room-name">
                    ${escapeHtml(
                        room.name ||
                        'Unnamed Room'
                    )}
                </div>

            </td>

            <td>

                <span class="booking-room-code">
                    ${escapeHtml(
                        room.room_code ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-time">
                    ${escapeHtml(
                        room.capacity ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <span class="booking-date">
                    ${escapeHtml(
                        room.floor ||
                        '—'
                    )}
                </span>

            </td>

            <td>

                <div class="booking-description">
                    ${escapeHtml(
                        room.description ||
                        '—'
                    )}
                </div>

            </td>

            <td>

                <span
                    class="booking-status booking-status-${statusClass}"
                >
                    ${status}
                </span>

            </td>

            <td>

                <div class="room-actions">

                    <button
                        type="button"
                        class="room-action-btn room-edit-btn"
                        data-room-id="${escapeHtml(
                            room.id
                        )}"
                        title="Edit room"
                        aria-label="Edit room"
                    >
                        <i class="bi bi-pencil"></i>
                    </button>

                    <button
                        type="button"
                        class="room-action-btn room-delete-btn"
                        data-room-id="${escapeHtml(
                            room.id
                        )}"
                        title="Delete room"
                        aria-label="Delete room"
                    >
                        <i class="bi bi-trash"></i>
                    </button>

                </div>

            </td>

        `;

        tableBody.appendChild(
            row
        );
    });

    setupRoomActionButtons();
}


/* ==========================================================================
   ROOM FILTERS
   ========================================================================== */

function setupRoomFilters() {

    const searchInput =
        document.getElementById(
            'roomSearch'
        );

    const statusFilter =
        document.getElementById(
            'roomStatusFilter'
        );

    searchInput?.addEventListener(
        'input',
        applyRoomFilters
    );

    statusFilter?.addEventListener(
        'change',
        applyRoomFilters
    );
}


function applyRoomFilters() {

    const searchInput =
        document.getElementById(
            'roomSearch'
        );

    const statusFilter =
        document.getElementById(
            'roomStatusFilter'
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
            : '';

    const filteredRooms =
        allRooms.filter(
            (room) => {

                const searchableText = [
                    room.name,
                    room.room_code,
                    room.floor,
                    room.description
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
                        room.is_active
                    ) ===
                    selectedStatus;

                return (
                    matchesSearch &&
                    matchesStatus
                );
            }
        );

    renderRooms(
        filteredRooms
    );
}


/* ==========================================================================
   ROOM MODAL
   ========================================================================== */

function setupRoomModal() {

    const modal =
        document.getElementById(
            'roomModal'
        );

    const newRoomBtn =
        document.getElementById(
            'newRoomBtn'
        );

    const closeRoomModal =
        document.getElementById(
            'closeRoomModal'
        );

    const cancelRoomBtn =
        document.getElementById(
            'cancelRoomBtn'
        );

    const form =
        document.getElementById(
            'roomForm'
        );

    if (
        !modal ||
        !newRoomBtn ||
        !form
    ) {
        return;
    }

    newRoomBtn.addEventListener(
        'click',
        () => {

            resetRoomForm();

            openRoomModal();
        }
    );

    closeRoomModal?.addEventListener(
        'click',
        closeRoomModalWindow
    );

    cancelRoomBtn?.addEventListener(
        'click',
        closeRoomModalWindow
    );

    modal.addEventListener(
        'click',
        (event) => {

            if (
                event.target === modal
            ) {
                closeRoomModalWindow();
            }
        }
    );

    form.addEventListener(
        'submit',
        handleRoomFormSubmit
    );
}


function openRoomModal() {

    const modal =
        document.getElementById(
            'roomModal'
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
            'roomName'
        )?.focus();

    }, 50);
}


function closeRoomModalWindow() {

    const modal =
        document.getElementById(
            'roomModal'
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


function resetRoomForm() {

    const form =
        document.getElementById(
            'roomForm'
        );

    form?.reset();

    const roomId =
        document.getElementById(
            'roomId'
        );

    if (roomId) {
        roomId.value = '';
    }

    const modalTitle =
        document.getElementById(
            'roomModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            'New Room';
    }

    const modalSubtitle =
        document.getElementById(
            'roomModalSubtitle'
        );

    if (modalSubtitle) {

        modalSubtitle.textContent =
            'Create a new meeting room.';
    }

    const submitButton =
        document.getElementById(
            'submitRoomBtn'
        );

    if (submitButton) {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Room
        `;
    }

    hideRoomFormMessages();
}


/* ==========================================================================
   ROOM ACTION BUTTONS
   ========================================================================== */

function setupRoomActionButtons() {

    const editButtons =
        document.querySelectorAll(
            '.room-edit-btn'
        );

    const deleteButtons =
        document.querySelectorAll(
            '.room-delete-btn'
        );

    editButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    const roomId =
                        button.dataset.roomId;

                    editRoom(
                        roomId
                    );
                }
            );
        }
    );

    deleteButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    const roomId =
                        button.dataset.roomId;

                    deleteRoom(
                        roomId
                    );
                }
            );
        }
    );
}


function findRoomById(roomId) {

    return allRooms.find(
        (room) =>
            String(room.id) ===
            String(roomId)
    );
}


/* ==========================================================================
   EDIT ROOM
   ========================================================================== */

function editRoom(roomId) {

    const room =
        findRoomById(
            roomId
        );

    if (!room) {

        showRoomFormError(
            'Unable to find the selected room.'
        );

        return;
    }

    const roomIdInput =
        document.getElementById(
            'roomId'
        );

    const roomNameInput =
        document.getElementById(
            'roomName'
        );

    const roomCodeInput =
        document.getElementById(
            'roomCode'
        );

    const roomLocationInput =
        document.getElementById(
            'roomLocation'
        );

    const roomCapacityInput =
        document.getElementById(
            'roomCapacity'
        );

    const roomFloorInput =
        document.getElementById(
            'roomFloor'
        );

    const roomStatusInput =
        document.getElementById(
            'roomStatus'
        );

    const roomDescriptionInput =
        document.getElementById(
            'roomDescription'
        );

    if (roomIdInput) {
        roomIdInput.value =
            room.id;
    }

    if (roomNameInput) {
        roomNameInput.value =
            room.name || '';
    }

    if (roomCodeInput) {
        roomCodeInput.value =
            room.room_code || '';
    }

    if (roomLocationInput) {
        roomLocationInput.value =
            room.location_id || '';
    }

    if (roomCapacityInput) {
        roomCapacityInput.value =
            room.capacity || '';
    }

    if (roomFloorInput) {
        roomFloorInput.value =
            room.floor || '';
    }

    if (roomStatusInput) {
        roomStatusInput.value =
            String(
                room.is_active ?? '1'
            );
    }

    if (roomDescriptionInput) {
        roomDescriptionInput.value =
            room.description || '';
    }

    const modalTitle =
        document.getElementById(
            'roomModalTitle'
        );

    if (modalTitle) {

        modalTitle.textContent =
            'Edit Room';
    }

    const modalSubtitle =
        document.getElementById(
            'roomModalSubtitle'
        );

    if (modalSubtitle) {

        modalSubtitle.textContent =
            'Update meeting room details.';
    }

    hideRoomFormMessages();

    openRoomModal();
}


/* ==========================================================================
   CREATE / UPDATE ROOM
   ========================================================================== */

async function handleRoomFormSubmit(
    event
) {

    event.preventDefault();

    const form =
        document.getElementById(
            'roomForm'
        );

    const submitButton =
        document.getElementById(
            'submitRoomBtn'
        );

    if (
        !form ||
        !submitButton
    ) {
        return;
    }

    hideRoomFormMessages();

    const roomId =
        document.getElementById(
            'roomId'
        ).value.trim();

    const name =
        document.getElementById(
            'roomName'
        ).value.trim();

    const roomCode =
        document.getElementById(
            'roomCode'
        ).value.trim();

    const locationId =
        document.getElementById(
            'roomLocation'
        ).value;

    const capacity =
        document.getElementById(
            'roomCapacity'
        ).value;

    const floor =
        document.getElementById(
            'roomFloor'
        ).value.trim();

    const description =
        document.getElementById(
            'roomDescription'
        ).value.trim();

    const isActive =
        document.getElementById(
            'roomStatus'
        ).value;

    if (
        !name ||
        !roomCode ||
        !locationId ||
        !capacity
    ) {

        showRoomFormError(
            'Please fill in all required fields.'
        );

        return;
    }

    if (
        Number(locationId) <= 0
    ) {

        showRoomFormError(
            'Location ID must be greater than zero.'
        );

        return;
    }

    if (
        Number(capacity) <= 0
    ) {

        showRoomFormError(
            'Room capacity must be greater than zero.'
        );

        return;
    }

    const payload = {

        location_id:
            Number(locationId),

        name:
            name,

        room_code:
            roomCode,

        capacity:
            Number(capacity),

        floor:
            floor,

        description:
            description,

        is_active:
            Number(isActive)
    };

    const isEditing =
        Boolean(roomId);

    const url =
        isEditing
            ? `/api/rooms/${roomId}`
            : '/api/rooms';

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
                    ? 'Updating...'
                    : 'Creating...'
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

            handleRoomApiError(
                response.status,
                result
            );

            return;
        }

        const successMessage =
            result.message ||
            (
                isEditing
                    ? 'Room updated successfully.'
                    : 'Room created successfully.'
            );

        showRoomFormSuccess(
            successMessage
        );

        showAppNotification(
            successMessage,
            'success',
            isEditing
                ? 'Room Updated'
                : 'Room Created'
        );

        await loadRooms();

        setTimeout(() => {

            closeRoomModalWindow();

        }, 800);

    } catch (error) {

        console.error(
            'Unable to save room:',
            error
        );

        showRoomFormError(
            'Unable to save room. Please try again.'
        );

        showAppNotification(
            'Unable to save room. Please try again.',
            'error',
            'Room Update Failed'
        );

    } finally {

        submitButton.disabled =
            false;

        submitButton.innerHTML = `
            <i class="bi bi-check-lg"></i>
            Save Room
        `;
    }
}


/* ==========================================================================
   DELETE ROOM
   ========================================================================== */

async function deleteRoom(roomId) {

    const room =
        findRoomById(
            roomId
        );

    if (!room) {

        showAppNotification(
            'Unable to find the selected room.',
            'error',
            'Room Not Found'
        );

        return;
    }

    /*
     * IMPORTANT:
     *
     * We no longer use:
     *
     * window.confirm()
     *
     * Instead, we show our custom
     * MeetSpace confirmation dialog.
     */

    showAppConfirm(

        `Are you sure you want to delete "${room.name}"?`,

        async () => {

            await performRoomDelete(
                roomId,
                room.name
            );
        },

        'Delete Room',

        'Delete Room'
    );
}


/**
 * Actually performs the room deletion
 * after the user clicks "Delete Room".
 */
async function performRoomDelete(
    roomId,
    roomName
) {

    try {

        const response =
            await fetch(
                `/api/rooms/${roomId}`,
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
                'Unable to delete room.'
            );
        }

        await loadRooms();

        /*
         * Success message appears
         * INSIDE the application.
         */

        showAppNotification(

            result.message ||
            `"${roomName}" has been deleted successfully.`,

            'success',

            'Room Deleted'
        );

    } catch (error) {

        console.error(
            'Unable to delete room:',
            error
        );

        /*
         * No browser alert().
         * Use application notification instead.
         */

        showAppNotification(

            error.message ||
            'Unable to delete room. Please try again.',

            'error',

            'Delete Failed'
        );
    }
}


/* ==========================================================================
   ROOM API ERROR HANDLING
   ========================================================================== */

function handleRoomApiError(
    status,
    result
) {

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

        showRoomFormError(
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
        status === 404
    ) {

        const message =
            result.message ||
            'Room or location could not be found.';

        showRoomFormError(
            message
        );

        showAppNotification(
            message,
            'error',
            'Room Not Found'
        );

        return;
    }

    const message =
        result.message ||
        'Unable to save room.';

    showRoomFormError(
        message
    );

    showAppNotification(
        message,
        'error',
        'Room Error'
    );
}


/* ==========================================================================
   ROOM FORM MESSAGES
   ========================================================================== */

function showRoomFormError(
    message
) {

    const errorBox =
        document.getElementById(
            'roomFormError'
        );

    const errorText =
        document.getElementById(
            'roomFormErrorText'
        );

    const successBox =
        document.getElementById(
            'roomFormSuccess'
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


function showRoomFormSuccess(
    message
) {

    const successBox =
        document.getElementById(
            'roomFormSuccess'
        );

    const successText =
        document.getElementById(
            'roomFormSuccessText'
        );

    const errorBox =
        document.getElementById(
            'roomFormError'
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


function hideRoomFormMessages() {

    document.getElementById(
        'roomFormError'
    )?.classList.add(
        'd-none'
    );

    document.getElementById(
        'roomFormSuccess'
    )?.classList.add(
        'd-none'
    );
}


/* ==========================================================================
   GLOBAL HELPERS
   ========================================================================== */

function escapeHtml(value) {

    return String(value)

        .replaceAll(
            '&',
            '&amp;'
        )

        .replaceAll(
            '<',
            '&lt;'
        )

        .replaceAll(
            '>',
            '&gt;'
        )

        .replaceAll(
            '"',
            '&quot;'
        )

        .replaceAll(
            "'",
            '&#039;'
        );
}