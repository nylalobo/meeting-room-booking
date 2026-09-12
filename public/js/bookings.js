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
            setupPendingApprovalsFilter();
            setupBookingRejectionModal();
            setupRoomAvailabilitySearch();
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
    let pendingApprovalsOnly = false;


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

            updatePendingApprovalsCount();

            if (pendingApprovalsOnly) {
                applyBookingFilters();
            } else {
                renderBookings(
                    allBookings
                );
            }

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

            let statusSubtext = '';
            if (normalizedStatus === 'rejected' && booking.rejection_reason) {
                const shortReason = booking.rejection_reason.length > 28
                    ? booking.rejection_reason.slice(0, 25) + '...'
                    : booking.rejection_reason;
                statusSubtext = `<span class="booking-meta-note booking-meta-rejected" title="${escapeHtml(booking.rejection_reason)}"><i class="bi bi-info-circle"></i> ${escapeHtml(shortReason)}</span>`;
            } else if (normalizedStatus === 'approved' && booking.approver_name) {
                statusSubtext = `<span class="booking-meta-note booking-meta-approver" title="Approved by ${escapeHtml(booking.approver_name)}"><i class="bi bi-check2"></i> By ${escapeHtml(booking.approver_name)}</span>`;
            }

            const canApprove = Boolean(booking.can_approve && normalizedStatus === 'pending');

            const organizerDept = booking.organizer_department_name
                ? `<div class="booking-description" style="font-size: 10.5px; opacity: 0.85;">${escapeHtml(booking.organizer_department_name)}</div>`
                : '';

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
                    ${organizerDept}
                </td>

                <td>
                    <span class="booking-status booking-status-${escapeHtml(normalizedStatus)}">
                        ${escapeHtml(statusLabel)}
                    </span>
                    ${statusSubtext}
                </td>

                <td>
                    <div class="booking-actions">
                        ${canApprove ? `
                            <button
                                type="button"
                                class="booking-action-btn booking-approve-btn"
                                data-booking-id="${escapeHtml(booking.id)}"
                                data-booking-title="${escapeHtml(booking.title || 'Untitled Meeting')}"
                                title="Approve booking"
                                aria-label="Approve booking"
                            >
                                <i class="bi bi-check-lg" style="color: #4ade80;"></i>
                            </button>

                            <button
                                type="button"
                                class="booking-action-btn booking-reject-btn"
                                data-booking-id="${escapeHtml(booking.id)}"
                                data-booking-title="${escapeHtml(booking.title || 'Untitled Meeting')}"
                                title="Reject booking"
                                aria-label="Reject booking"
                            >
                                <i class="bi bi-x-lg" style="color: #f87171;"></i>
                            </button>
                        ` : ''}

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
            () => {
                if (pendingApprovalsOnly) {
                    pendingApprovalsOnly = false;
                    document.getElementById('pendingApprovalsBtn')?.classList.remove('active');
                }
                applyBookingFilters();
            }
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

                if (pendingApprovalsOnly) {
                    if (String(booking.status || '').toLowerCase() !== 'pending' || !booking.can_approve) {
                        return false;
                    }
                }

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

        setupRecurrenceControls();
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
        resetRecurrenceForm();
    }


    /* ==========================================================================
       RECURRENCE CONTROLS (MILESTONE 3)
       ========================================================================== */

    function setupRecurrenceControls() {

        const isRecurringCheckbox =
            document.getElementById('bookingIsRecurring');

        const recurrenceContainer =
            document.getElementById('recurrenceFieldsContainer');

        const frequencySelect =
            document.getElementById('recurrenceFrequency');

        const intervalUnit =
            document.getElementById('recurrenceIntervalUnit');

        const daysOfWeekGroup =
            document.getElementById('recurrenceDaysOfWeekGroup');

        const monthlyNote =
            document.getElementById('recurrenceMonthlyNote');

        const weekdayButtons =
            document.querySelectorAll('.weekday-btn');

        const endTypeOccurrences =
            document.getElementById('recurrenceEndTypeOccurrences');

        const endTypeDate =
            document.getElementById('recurrenceEndTypeDate');

        const occurrencesInput =
            document.getElementById('recurrenceOccurrences');

        const untilDateInput =
            document.getElementById('recurrenceUntilDate');

        const previewBtn =
            document.getElementById('previewRecurrenceBtn');

        const startInput =
            document.getElementById('bookingStart');

        if (!isRecurringCheckbox || !recurrenceContainer) {
            return;
        }

        // Toggle recurrence container
        isRecurringCheckbox.addEventListener('change', () => {
            if (isRecurringCheckbox.checked) {
                recurrenceContainer.classList.remove('d-none');
                autoSelectInitialWeekday();
            } else {
                recurrenceContainer.classList.add('d-none');
                hideRecurrencePreview();
            }
        });

        // Frequency change handler
        frequencySelect?.addEventListener('change', () => {
            const freq = frequencySelect.value;

            if (intervalUnit) {
                switch (freq) {
                    case 'daily':
                        intervalUnit.textContent = 'day(s)';
                        break;
                    case 'weekly':
                        intervalUnit.textContent = 'week(s)';
                        break;
                    case 'biweekly':
                        intervalUnit.textContent = 'two-week period(s)';
                        break;
                    case 'monthly':
                        intervalUnit.textContent = 'month(s)';
                        break;
                    case 'weekdays':
                        intervalUnit.textContent = 'weekday cycle(s)';
                        break;
                    default:
                        intervalUnit.textContent = 'period(s)';
                }
            }

            if (daysOfWeekGroup) {
                if (freq === 'weekly' || freq === 'biweekly') {
                    daysOfWeekGroup.classList.remove('d-none');
                    autoSelectInitialWeekday();
                } else {
                    daysOfWeekGroup.classList.add('d-none');
                }
            }

            if (monthlyNote) {
                if (freq === 'monthly') {
                    monthlyNote.classList.remove('d-none');
                } else {
                    monthlyNote.classList.add('d-none');
                }
            }

            hideRecurrencePreview();
        });

        // Weekday buttons toggle
        weekdayButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                btn.classList.toggle('active');
                hideRecurrencePreview();
            });
        });

        // End condition radios
        endTypeOccurrences?.addEventListener('change', () => {
            if (endTypeOccurrences.checked) {
                if (occurrencesInput) occurrencesInput.disabled = false;
                if (untilDateInput) {
                    untilDateInput.disabled = true;
                    untilDateInput.value = '';
                }
                hideRecurrencePreview();
            }
        });

        endTypeDate?.addEventListener('change', () => {
            if (endTypeDate.checked) {
                if (untilDateInput) untilDateInput.disabled = false;
                if (occurrencesInput) occurrencesInput.disabled = true;
                hideRecurrencePreview();
            }
        });

        // Start time input updates default weekday and min date
        startInput?.addEventListener('change', () => {
            autoSelectInitialWeekday();
            if (untilDateInput && startInput.value) {
                const startDateStr = startInput.value.split('T')[0];
                untilDateInput.min = startDateStr;
            }
        });

        // Preview button click
        previewBtn?.addEventListener('click', handleRecurrencePreview);
    }


    function autoSelectInitialWeekday() {

        const startInput =
            document.getElementById('bookingStart');

        const weekdayButtons =
            document.querySelectorAll('.weekday-btn');

        const hasActive =
            Array.from(weekdayButtons).some((b) => b.classList.contains('active'));

        if (hasActive) {
            return;
        }

        if (startInput && startInput.value) {
            const startDate = new Date(startInput.value);
            if (!isNaN(startDate.getTime())) {
                const day = startDate.getDay();
                const isoDay = day === 0 ? 7 : day;
                const targetBtn = document.querySelector(`.weekday-btn[data-dow="${isoDay}"]`);
                targetBtn?.classList.add('active');
            }
        }
    }


    function getSelectedWeekdays() {

        const activeBtns =
            document.querySelectorAll('.weekday-btn.active');

        const days = Array.from(activeBtns)
            .map((b) => Number(b.dataset.dow))
            .filter((n) => n >= 1 && n <= 7);

        days.sort((a, b) => a - b);
        return days;
    }


    function hideRecurrencePreview() {

        const previewContainer =
            document.getElementById('recurrencePreviewContainer');

        if (previewContainer) {
            previewContainer.classList.add('d-none');
            previewContainer.innerHTML = '';
        }
    }


    function resetRecurrenceForm() {

        const isRecurringCheckbox =
            document.getElementById('bookingIsRecurring');

        const recurrenceContainer =
            document.getElementById('recurrenceFieldsContainer');

        const frequencySelect =
            document.getElementById('recurrenceFrequency');

        const intervalInput =
            document.getElementById('recurrenceInterval');

        const intervalUnit =
            document.getElementById('recurrenceIntervalUnit');

        const daysOfWeekGroup =
            document.getElementById('recurrenceDaysOfWeekGroup');

        const monthlyNote =
            document.getElementById('recurrenceMonthlyNote');

        const weekdayButtons =
            document.querySelectorAll('.weekday-btn');

        const endTypeOccurrences =
            document.getElementById('recurrenceEndTypeOccurrences');

        const endTypeDate =
            document.getElementById('recurrenceEndTypeDate');

        const occurrencesInput =
            document.getElementById('recurrenceOccurrences');

        const untilDateInput =
            document.getElementById('recurrenceUntilDate');

        const previewBtn =
            document.getElementById('previewRecurrenceBtn');

        const recurrenceToggleSection =
            document.querySelector('.recurrence-toggle-section');

        if (recurrenceToggleSection) {
            recurrenceToggleSection.classList.remove('d-none');
        }

        if (isRecurringCheckbox) {
            isRecurringCheckbox.checked = false;
        }

        if (recurrenceContainer) {
            recurrenceContainer.classList.add('d-none');
        }

        if (frequencySelect) {
            frequencySelect.value = 'weekly';
        }

        if (intervalInput) {
            intervalInput.value = '1';
        }

        if (intervalUnit) {
            intervalUnit.textContent = 'week(s)';
        }

        if (daysOfWeekGroup) {
            daysOfWeekGroup.classList.remove('d-none');
        }

        if (monthlyNote) {
            monthlyNote.classList.add('d-none');
        }

        weekdayButtons.forEach((btn) => btn.classList.remove('active'));

        if (endTypeOccurrences) {
            endTypeOccurrences.checked = true;
        }

        if (occurrencesInput) {
            occurrencesInput.disabled = false;
            occurrencesInput.value = '5';
        }

        if (endTypeDate) {
            endTypeDate.checked = false;
        }

        if (untilDateInput) {
            untilDateInput.disabled = true;
            untilDateInput.value = '';
        }

        if (previewBtn) {
            previewBtn.disabled = false;
            previewBtn.innerHTML = `
                <i class="bi bi-eye"></i>
                Preview Occurrences & Check Conflicts
            `;
        }

        hideRecurrencePreview();
    }


    async function handleRecurrencePreview() {

        hideBookingFormMessages();

        const roomId =
            document.getElementById('bookingRoom')?.value;

        const startTimeLocal =
            document.getElementById('bookingStart')?.value;

        const endTimeLocal =
            document.getElementById('bookingEnd')?.value;

        if (!roomId || !startTimeLocal || !endTimeLocal) {
            showBookingFormError('Please select Room, Start Time, and End Time before previewing recurrence.');
            return;
        }

        if (new Date(startTimeLocal) >= new Date(endTimeLocal)) {
            showBookingFormError('End time must be after start time.');
            return;
        }

        const frequency =
            document.getElementById('recurrenceFrequency')?.value || 'weekly';

        const interval =
            Number(document.getElementById('recurrenceInterval')?.value) || 1;

        const endType =
            document.querySelector('input[name="recurrence_end_type"]:checked')?.value || 'occurrences';

        const recurrenceConfig = {
            frequency: frequency,
            interval: interval,
            end_type: endType
        };

        if (frequency === 'weekly' || frequency === 'biweekly') {
            const days = getSelectedWeekdays();
            if (days.length > 0) {
                recurrenceConfig.days_of_week = days;
            }
        }

        if (endType === 'occurrences') {
            const occ = Number(document.getElementById('recurrenceOccurrences')?.value);
            if (!occ || occ < 1) {
                showBookingFormError('Number of occurrences must be at least 1.');
                return;
            }
            if (occ > 52) {
                showBookingFormError('Number of occurrences cannot exceed 52.');
                return;
            }
            recurrenceConfig.occurrences = occ;
        } else if (endType === 'date') {
            const untilDate = document.getElementById('recurrenceUntilDate')?.value;
            if (!untilDate) {
                showBookingFormError('Please select a valid end date for the recurrence.');
                return;
            }
            const startDateStr = startTimeLocal.split('T')[0];
            if (untilDate < startDateStr) {
                showBookingFormError('End date cannot be earlier than start date.');
                return;
            }
            recurrenceConfig.until_date = untilDate;
        }

        const previewBtn =
            document.getElementById('previewRecurrenceBtn');

        if (previewBtn) {
            previewBtn.disabled = true;
            previewBtn.innerHTML = `
                <i class="bi bi-arrow-repeat spin"></i>
                Checking Preview...
            `;
        }

        const startTimeApi = formatDateTimeForApi(startTimeLocal);
        const endTimeApi = formatDateTimeForApi(endTimeLocal);

        try {

            const response = await fetch('/api/bookings/recurring-preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    room_id: Number(roomId),
                    start_time: startTimeApi,
                    end_time: endTimeApi,
                    recurrence: recurrenceConfig
                })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                const errorMsg = result.errors
                    ? Object.values(result.errors).join(' ')
                    : (result.message || 'Failed to preview recurring bookings.');
                showBookingFormError(errorMsg);
                hideRecurrencePreview();
                return;
            }

            renderRecurrencePreview(result.data);

        } catch (error) {

            showBookingFormError('Network error while checking recurrence preview. Please try again.');
            hideRecurrencePreview();

        } finally {

            if (previewBtn) {
                previewBtn.disabled = false;
                previewBtn.innerHTML = `
                    <i class="bi bi-eye"></i>
                    Preview Occurrences & Check Conflicts
                `;
            }
        }
    }


    function renderRecurrencePreview(data) {

        const previewContainer =
            document.getElementById('recurrencePreviewContainer');

        if (!previewContainer) {
            return;
        }

        const total = data.total_occurrences || 0;
        const available = data.available_occurrences || 0;
        const conflicts = data.conflicts_count || 0;
        const occurrences = data.occurrences || [];

        let html = `
            <div class="recurrence-preview-stats">
                <span class="preview-stat-pill preview-stat-total">
                    <i class="bi bi-calendar3"></i> Total: ${total}
                </span>
                <span class="preview-stat-pill preview-stat-available">
                    <i class="bi bi-check-circle"></i> Available: ${available}
                </span>
                <span class="preview-stat-pill preview-stat-conflicts">
                    <i class="bi bi-exclamation-triangle"></i> Conflicts: ${conflicts}
                </span>
            </div>
        `;

        if (conflicts > 0) {
            html += `
                <div class="recurrence-conflict-alert" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Room Conflict Detected:</strong> ${conflicts} occurrence(s) conflict with existing bookings. Adjust dates or select an alternative room before submitting.
                    </div>
                </div>
            `;
        }

        html += `<div class="recurrence-occurrences-list">`;

        occurrences.forEach((occ) => {
            const isConflict = Boolean(occ.is_conflict);
            const startFormatted = formatDateForDisplay(occ.start_time);
            const timeRange = formatTimeRangeForDisplay(occ.start_time, occ.end_time);

            let conflictDetails = '';
            if (isConflict && occ.conflicts && occ.conflicts.length > 0) {
                conflictDetails = occ.conflicts.map((c) => `
                    <span class="occurrence-conflict-info">
                        Conflict with: "${escapeHtml(c.conflicting_title || 'Booked')}" (${formatTimeRangeForDisplay(c.conflicting_start, c.conflicting_end)})
                    </span>
                `).join('');
            }

            html += `
                <div class="occurrence-item-row ${isConflict ? 'is-conflict' : ''}">
                    <div class="occurrence-meta">
                        <span class="occurrence-num">#${occ.occurrence_index}</span>
                        <div>
                            <span class="occurrence-date-time">
                                ${startFormatted} (${timeRange})
                            </span>
                            ${conflictDetails}
                        </div>
                    </div>
                    <span class="occurrence-badge-status ${isConflict ? 'occurrence-badge-conflict' : 'occurrence-badge-available'}">
                        ${isConflict ? '<i class="bi bi-x-circle"></i> Conflict' : '<i class="bi bi-check-circle"></i> Available'}
                    </span>
                </div>
            `;
        });

        html += `</div>`;

        previewContainer.innerHTML = html;
        previewContainer.classList.remove('d-none');
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

        const approveButtons =
            document.querySelectorAll(
                '.booking-approve-btn'
            );

        const rejectButtons =
            document.querySelectorAll(
                '.booking-reject-btn'
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

        approveButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = button.dataset.bookingId;
                    const bookingTitle = button.dataset.bookingTitle || 'this booking';
                    confirmApproveBooking(bookingId, bookingTitle);
                }
            );
        });

        rejectButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = button.dataset.bookingId;
                    openBookingRejectionModal(bookingId);
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

        const recurrenceToggleSection =
            document.querySelector('.recurrence-toggle-section');

        if (recurrenceToggleSection) {
            recurrenceToggleSection.classList.add('d-none');
        }

        resetRecurrenceForm();

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
        const isRecurring = !isEditing && Boolean(document.getElementById('bookingIsRecurring')?.checked);

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

        if (isRecurring) {
            const frequency =
                document.getElementById('recurrenceFrequency')?.value || 'weekly';

            const interval =
                Number(document.getElementById('recurrenceInterval')?.value) || 1;

            const endType =
                document.querySelector('input[name="recurrence_end_type"]:checked')?.value || 'occurrences';

            const recurrenceConfig = {
                frequency: frequency,
                interval: interval,
                end_type: endType
            };

            if (frequency === 'weekly' || frequency === 'biweekly') {
                const days = getSelectedWeekdays();
                if (days.length > 0) {
                    recurrenceConfig.days_of_week = days;
                }
            }

            if (endType === 'occurrences') {
                const occ = Number(document.getElementById('recurrenceOccurrences')?.value);
                if (!occ || occ < 1) {
                    showBookingFormError('Number of occurrences must be at least 1.');
                    return;
                }
                if (occ > 52) {
                    showBookingFormError('Number of occurrences cannot exceed 52.');
                    return;
                }
                recurrenceConfig.occurrences = occ;
            } else if (endType === 'date') {
                const untilDate = document.getElementById('recurrenceUntilDate')?.value;
                if (!untilDate) {
                    showBookingFormError('Please select a valid end date for the recurrence.');
                    return;
                }
                const startDateStr = startTimeLocal.split('T')[0];
                if (untilDate < startDateStr) {
                    showBookingFormError('End date cannot be earlier than start date.');
                    return;
                }
                recurrenceConfig.until_date = untilDate;
            }

            payload.is_recurring = true;
            payload.recurrence = recurrenceConfig;
        }

        const url = isEditing
            ? `/api/bookings/${bookingId}`
            : '/api/bookings';

        const method = isEditing ? 'PUT' : 'POST';

        try {

            submitButton.disabled = true;
            submitButton.innerHTML = `
                <i class="bi bi-arrow-repeat spin"></i>
                ${isEditing ? 'Updating...' : (isRecurring ? 'Creating Series...' : 'Creating...')}
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
                if (isRecurring && response.status === 409 && result.data) {
                    renderRecurrencePreview(result.data);
                }
                handleBookingApiError(response.status, result);
                return;
            }

            let successMessage = result.message;
            if (!successMessage) {
                if (isEditing) {
                    successMessage = 'Booking updated successfully.';
                } else if (isRecurring) {
                    const count = result.data?.total_occurrences || result.data?.created_ids?.length;
                    successMessage = count
                        ? `Recurring booking series created successfully — ${count} occurrences.`
                        : 'Recurring booking series created successfully.';
                } else {
                    successMessage = 'Booking created successfully.';
                }
            }

            showBookingFormSuccess(successMessage);

            showAppNotification(
                successMessage,
                'success',
                isEditing ? 'Booking Updated' : (isRecurring ? 'Recurring Series Created' : 'Booking Created')
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
       APPROVAL WORKFLOW
       ========================================================================== */

    function confirmApproveBooking(bookingId, titleText) {

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
        const meetingTitle = titleText || booking.title || 'this meeting';

        showAppConfirm(
            `Are you sure you want to approve "${meetingTitle}" (${dateText})? Once approved, the room is officially reserved.`,
            async () => {
                await performBookingApprove(bookingId, meetingTitle);
            },
            'Approve Booking',
            'Approve Booking'
        );
    }


    async function performBookingApprove(bookingId, titleText) {

        try {

            const response = await fetch(`/api/bookings/${bookingId}/approve`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Unable to approve booking.');
            }

            await loadBookingsData();

            showAppNotification(
                result.message || `"${titleText}" has been approved successfully.`,
                'success',
                'Booking Approved'
            );

        } catch (error) {

            showAppNotification(
                error.message || 'Unable to approve booking. Please try again.',
                'error',
                'Approval Failed'
            );
        }
    }


    /* ==========================================================================
       REJECTION WORKFLOW
       ========================================================================== */

    function setupBookingRejectionModal() {

        const modal =
            document.getElementById('bookingRejectionModal');

        const closeBtn =
            document.getElementById('closeBookingRejectionModal');

        const cancelBtn =
            document.getElementById('cancelBookingRejectionBtn');

        const form =
            document.getElementById('bookingRejectionForm');

        if (!modal || !form) {
            return;
        }

        closeBtn?.addEventListener(
            'click',
            closeBookingRejectionModalWindow
        );

        cancelBtn?.addEventListener(
            'click',
            closeBookingRejectionModalWindow
        );

        modal.addEventListener(
            'click',
            (event) => {
                if (event.target === modal) {
                    closeBookingRejectionModalWindow();
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
                    closeBookingRejectionModalWindow();
                }
            }
        );

        form.addEventListener(
            'submit',
            handleBookingRejectionSubmit
        );
    }


    function openBookingRejectionModal(bookingId) {

        const booking = findBookingById(bookingId);

        if (!booking) {
            showAppNotification(
                'Unable to find the selected booking.',
                'error',
                'Booking Not Found'
            );
            return;
        }

        const modal =
            document.getElementById('bookingRejectionModal');

        const idInput =
            document.getElementById('rejectionBookingId');

        const reasonInput =
            document.getElementById('rejectionReasonInput');

        const errorBox =
            document.getElementById('bookingRejectionError');

        const errorText =
            document.getElementById('bookingRejectionErrorText');

        const subtitle =
            document.getElementById('bookingRejectionSubtitle');

        if (!modal) {
            return;
        }

        if (idInput) {
            idInput.value = String(bookingId);
        }

        if (reasonInput) {
            reasonInput.value = '';
        }

        if (subtitle) {
            subtitle.textContent =
                `Provide a clear reason for rejecting "${booking.title || 'this meeting'}".`;
        }

        errorBox?.classList.add('d-none');

        if (errorText) {
            errorText.textContent = '';
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        setTimeout(() => {
            reasonInput?.focus();
        }, 50);
    }


    function closeBookingRejectionModalWindow() {

        const modal =
            document.getElementById('bookingRejectionModal');

        if (!modal) {
            return;
        }

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');

        const errorBox =
            document.getElementById('bookingRejectionError');

        errorBox?.classList.add('d-none');
    }


    async function handleBookingRejectionSubmit(event) {

        event.preventDefault();

        const idInput =
            document.getElementById('rejectionBookingId');

        const reasonInput =
            document.getElementById('rejectionReasonInput');

        const submitBtn =
            document.getElementById('submitBookingRejectionBtn');

        const errorBox =
            document.getElementById('bookingRejectionError');

        const errorText =
            document.getElementById('bookingRejectionErrorText');

        const bookingId =
            idInput ? idInput.value : '';

        const reason =
            reasonInput ? reasonInput.value.trim() : '';

        if (!bookingId) {
            return;
        }

        if (!reason || reason.length < 3) {
            if (errorBox && errorText) {
                errorText.textContent =
                    'Please provide a meaningful reason (at least 3 characters).';
                errorBox.classList.remove('d-none');
            }
            reasonInput?.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<i class="bi bi-arrow-repeat spin"></i> Rejecting...';
        }

        try {

            const response = await fetch(`/api/bookings/${bookingId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                const msg =
                    result.message || 'Unable to reject booking.';

                if (errorBox && errorText) {
                    errorText.textContent = msg;
                    errorBox.classList.remove('d-none');
                }

                showAppNotification(msg, 'error', 'Rejection Failed');
                return;
            }

            closeBookingRejectionModalWindow();
            await loadBookingsData();

            showAppNotification(
                result.message || 'Booking has been rejected.',
                'success',
                'Booking Rejected'
            );

        } catch (error) {

            const msg =
                error.message || 'Network error occurred while rejecting.';

            if (errorBox && errorText) {
                errorText.textContent = msg;
                errorBox.classList.remove('d-none');
            }

        } finally {

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML =
                    '<i class="bi bi-x-circle"></i> Confirm Rejection';
            }
        }
    }


    /* ==========================================================================
       PENDING APPROVALS TOOLBAR FILTER
       ========================================================================== */

    function setupPendingApprovalsFilter() {

        const pendingBtn =
            document.getElementById('pendingApprovalsBtn');

        if (!pendingBtn) {
            return;
        }

        const currentUser =
            window.MeetSpaceUser || null;

        const isApproverRole =
            currentUser &&
            ['Admin', 'Facilities Manager', 'Manager'].includes(
                currentUser.role_name
            );

        if (!isApproverRole) {
            pendingBtn.classList.add('d-none');
            return;
        }

        pendingBtn.classList.remove('d-none');

        pendingBtn.addEventListener('click', () => {

            pendingApprovalsOnly = !pendingApprovalsOnly;
            pendingBtn.classList.toggle('active', pendingApprovalsOnly);

            const statusFilter =
                document.getElementById('statusFilter');

            if (pendingApprovalsOnly && statusFilter) {
                statusFilter.value = '';
            }

            applyBookingFilters();
        });
    }


    function updatePendingApprovalsCount() {

        const badge =
            document.getElementById('pendingApprovalsCount');

        if (!badge) {
            return;
        }

        const count = allBookings.filter(
            (b) =>
                String(b.status || '').toLowerCase() === 'pending' &&
                b.can_approve
        ).length;

        badge.textContent = String(count);
    }


    /* ==========================================================================
       ROOM AVAILABILITY & SEARCH WORKFLOW
       ========================================================================== */

    let cachedLocations = null;
    let cachedFacilities = null;

    function setupRoomAvailabilitySearch() {

        const modal = document.getElementById('availabilityModal');
        const findBtn = document.getElementById('findAvailableRoomBtn');
        const closeBtn = document.getElementById('closeAvailabilityModal');
        const form = document.getElementById('availabilityForm');

        if (!modal || !form) {
            return;
        }

        findBtn?.addEventListener('click', () => {
            openAvailabilityModal();
        });

        closeBtn?.addEventListener('click', closeAvailabilityModalWindow);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAvailabilityModalWindow();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
                closeAvailabilityModalWindow();
            }
        });

        form.addEventListener('submit', handleAvailabilitySearchSubmit);
    }

    async function openAvailabilityModal() {

        const modal = document.getElementById('availabilityModal');
        const startInput = document.getElementById('availStart');
        const endInput = document.getElementById('availEnd');
        const locationSelect = document.getElementById('availLocation');
        const specificRoomSelect = document.getElementById('availSpecificRoom');
        const facilitiesList = document.getElementById('availFacilitiesList');
        const resultsContainer = document.getElementById('availabilityResultsContainer');
        const errorBox = document.getElementById('availabilityError');

        if (!modal) {
            return;
        }

        errorBox?.classList.add('d-none');
        resultsContainer?.classList.add('d-none');

        // Set default dates if empty (next top of the hour, duration 1 hour)
        if (startInput && !startInput.value) {
            const now = new Date();
            now.setHours(now.getHours() + 1, 0, 0, 0);
            const nextHour = new Date(now);
            nextHour.setHours(nextHour.getHours() + 1);

            const pad = (n) => String(n).padStart(2, '0');
            const formatForInput = (d) =>
                `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

            startInput.value = formatForInput(now);
            if (endInput && !endInput.value) {
                endInput.value = formatForInput(nextHour);
            }
        }

        // Populate specific room dropdown from allRooms
        if (specificRoomSelect) {
            const currentRoomVal = specificRoomSelect.value;
            specificRoomSelect.innerHTML = '<option value="">Any Room</option>';
            allRooms
                .filter((r) => String(r.is_active) === '1')
                .forEach((r) => {
                    const opt = document.createElement('option');
                    opt.value = String(r.id);
                    opt.textContent = `${r.name} (${r.room_code || 'No code'}) - Cap: ${r.capacity}`;
                    specificRoomSelect.appendChild(opt);
                });
            if (currentRoomVal) {
                specificRoomSelect.value = currentRoomVal;
            }
        }

        // Load locations if not loaded
        if (locationSelect && !cachedLocations) {
            try {
                const res = await fetch('/api/locations');
                if (res.ok) {
                    const data = await res.json();
                    cachedLocations = data.data || [];
                    locationSelect.innerHTML = '<option value="">All Locations</option>';
                    cachedLocations
                        .filter((loc) => String(loc.is_active) === '1')
                        .forEach((loc) => {
                            const opt = document.createElement('option');
                            opt.value = String(loc.id);
                            opt.textContent = loc.name;
                            locationSelect.appendChild(opt);
                        });
                }
            } catch (err) {
                // Ignore network error on location pre-fetch
            }
        }

        // Load facilities if not loaded
        if (facilitiesList && !cachedFacilities) {
            try {
                const res = await fetch('/api/facilities');
                if (res.ok) {
                    const data = await res.json();
                    cachedFacilities = data.data || [];
                    facilitiesList.innerHTML = '';
                    cachedFacilities
                        .filter((f) => String(f.is_active) === '1')
                        .forEach((f) => {
                            const label = document.createElement('label');
                            label.className = 'facility-checkbox-item';
                            label.innerHTML = `
                                <input type="checkbox" name="facilities[]" value="${escapeHtml(f.id)}">
                                <span>${escapeHtml(f.name)}</span>
                            `;
                            facilitiesList.appendChild(label);
                        });
                }
            } catch (err) {
                // Ignore network error on facilities pre-fetch
            }
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');
    }

    function closeAvailabilityModalWindow() {

        const modal = document.getElementById('availabilityModal');
        if (!modal) {
            return;
        }

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
    }

    async function handleAvailabilitySearchSubmit(event) {

        event.preventDefault();

        const startInput = document.getElementById('availStart');
        const endInput = document.getElementById('availEnd');
        const locationSelect = document.getElementById('availLocation');
        const capacityInput = document.getElementById('availCapacity');
        const specificRoomSelect = document.getElementById('availSpecificRoom');
        const facilitiesList = document.getElementById('availFacilitiesList');
        const submitBtn = document.getElementById('submitAvailabilitySearchBtn');
        const errorBox = document.getElementById('availabilityError');
        const errorText = document.getElementById('availabilityErrorText');
        const resultsContainer = document.getElementById('availabilityResultsContainer');
        const loading = document.getElementById('availabilityLoading');
        const unavailableAlert = document.getElementById('requestedRoomUnavailableAlert');
        const availableAlert = document.getElementById('requestedRoomAvailableAlert');
        const alternativesSection = document.getElementById('alternativeSuggestionsWrapper');
        const availableSection = document.getElementById('availableRoomsWrapper');
        const emptyState = document.getElementById('availabilityEmptyState');

        errorBox?.classList.add('d-none');

        const startVal = startInput?.value;
        const endVal = endInput?.value;

        if (!startVal || !endVal) {
            if (errorBox && errorText) {
                errorText.textContent = 'Please select both start time and end time.';
                errorBox.classList.remove('d-none');
            }
            return;
        }

        if (endVal <= startVal) {
            if (errorBox && errorText) {
                errorText.textContent = 'End time must be strictly after start time.';
                errorBox.classList.remove('d-none');
            }
            return;
        }

        const payload = {
            start_time: formatDateTimeForApi(startVal),
            end_time: formatDateTimeForApi(endVal)
        };

        if (locationSelect && locationSelect.value) {
            payload.location_id = Number(locationSelect.value);
        }

        if (capacityInput && capacityInput.value && Number(capacityInput.value) > 0) {
            payload.capacity = Number(capacityInput.value);
        }

        const specificRoomId = specificRoomSelect && specificRoomSelect.value ? Number(specificRoomSelect.value) : null;
        if (specificRoomId) {
            payload.room_id = specificRoomId;
        }

        if (facilitiesList) {
            const checkedBoxes = Array.from(facilitiesList.querySelectorAll('input[type="checkbox"]:checked'));
            if (checkedBoxes.length > 0) {
                payload.facilities = checkedBoxes.map((cb) => Number(cb.value));
            }
        }

        resultsContainer?.classList.remove('d-none');
        loading?.classList.remove('d-none');
        unavailableAlert?.classList.add('d-none');
        availableAlert?.classList.add('d-none');
        alternativesSection?.classList.add('d-none');
        availableSection?.classList.add('d-none');
        emptyState?.classList.add('d-none');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Searching...';
        }

        try {
            const res = await fetch('/api/rooms/availability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await res.json();

            loading?.classList.add('d-none');

            if (!res.ok || result.status !== 'success') {
                if (errorBox && errorText) {
                    errorText.textContent = result.message || 'Error checking availability.';
                    errorBox.classList.remove('d-none');
                }
                return;
            }

            renderAvailabilitySearchResults(result.data, {
                startInputVal: startVal,
                endInputVal: endVal,
                specificRoomId: specificRoomId
            });

        } catch (err) {
            loading?.classList.add('d-none');
            if (errorBox && errorText) {
                errorText.textContent = err.message || 'Network error checking availability.';
                errorBox.classList.remove('d-none');
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-search"></i> Search Available Rooms';
            }
        }
    }

    function renderAvailabilitySearchResults(data, params) {

        const unavailableAlert = document.getElementById('requestedRoomUnavailableAlert');
        const unavailableRoomName = document.getElementById('unavailableRoomName');
        const unavailableConflicts = document.getElementById('unavailableRoomConflicts');
        const availableAlert = document.getElementById('requestedRoomAvailableAlert');
        const availableRoomName = document.getElementById('availableRoomName');
        const bookRequestedRoomBtn = document.getElementById('bookRequestedRoomBtn');
        const alternativesSection = document.getElementById('alternativeSuggestionsWrapper');
        const alternativesCount = document.getElementById('alternativesCount');
        const alternativeRoomsList = document.getElementById('alternativeRoomsList');
        const availableSection = document.getElementById('availableRoomsWrapper');
        const availableCount = document.getElementById('availableCount');
        const availableRoomsList = document.getElementById('availableRoomsList');
        const emptyState = document.getElementById('availabilityEmptyState');

        unavailableAlert?.classList.add('d-none');
        availableAlert?.classList.add('d-none');
        alternativesSection?.classList.add('d-none');
        availableSection?.classList.add('d-none');
        emptyState?.classList.add('d-none');

        if (params.specificRoomId) {
            if (data.is_available === false) {
                if (unavailableAlert) {
                    unavailableAlert.classList.remove('d-none');
                    if (unavailableRoomName) {
                        unavailableRoomName.textContent = data.room
                            ? `${data.room.name} (${data.room.room_code || 'No Code'}) is Unavailable`
                            : 'Selected Room is Unavailable';
                    }
                    if (unavailableConflicts) {
                        unavailableConflicts.innerHTML = '';
                        if (Array.isArray(data.conflicts) && data.conflicts.length > 0) {
                            data.conflicts.forEach((c) => {
                                const item = document.createElement('div');
                                item.className = 'conflict-interval-item';
                                item.innerHTML = `
                                    <i class="bi bi-clock-history"></i>
                                    <span>Conflict: <strong>${formatDateForDisplay(c.start_time)} ${formatTimeRangeForDisplay(c.start_time, c.end_time)}</strong> (Status: <em>${escapeHtml(c.status || '')}</em>)</span>
                                `;
                                unavailableConflicts.appendChild(item);
                            });
                        }
                    }
                }

                const alts = Array.isArray(data.alternatives) ? data.alternatives : [];
                if (alts.length > 0) {
                    if (alternativesSection && alternativeRoomsList && alternativesCount) {
                        alternativesSection.classList.remove('d-none');
                        alternativesCount.textContent = String(alts.length);
                        alternativeRoomsList.innerHTML = alts
                            .map((room) => buildAvailabilityRoomCard(room, true))
                            .join('');
                    }
                } else {
                    emptyState?.classList.remove('d-none');
                }

            } else {
                if (availableAlert) {
                    availableAlert.classList.remove('d-none');
                    if (availableRoomName) {
                        availableRoomName.textContent = data.room
                            ? `${data.room.name} is Available!`
                            : 'Room is Available!';
                    }
                    if (bookRequestedRoomBtn && data.room) {
                        bookRequestedRoomBtn.onclick = () => {
                            selectRoomAndOpenBooking(data.room.id, data.room.name, params.startInputVal, params.endInputVal);
                        };
                    }
                }
            }

        } else {
            const rooms = Array.isArray(data.available_rooms) ? data.available_rooms : [];
            if (rooms.length > 0) {
                if (availableSection && availableRoomsList && availableCount) {
                    availableSection.classList.remove('d-none');
                    availableCount.textContent = String(rooms.length);
                    availableRoomsList.innerHTML = rooms
                        .map((room) => buildAvailabilityRoomCard(room, false))
                        .join('');
                }
            } else {
                emptyState?.classList.remove('d-none');
            }
        }

        document.querySelectorAll('.btn-select-room-for-booking').forEach((btn) => {
            btn.addEventListener('click', () => {
                const roomId = btn.getAttribute('data-room-id');
                const roomName = btn.getAttribute('data-room-name');
                selectRoomAndOpenBooking(roomId, roomName, params.startInputVal, params.endInputVal);
            });
        });
    }

    function buildAvailabilityRoomCard(room, isAlternative) {

        const facilities = Array.isArray(room.facilities) ? room.facilities : [];
        const facilitiesHtml = facilities
            .map((f) => {
                const fName = typeof f === 'object' ? (f.name || '') : String(f);
                return `<span class="room-facility-pill"><i class="bi bi-check2"></i> ${escapeHtml(fName)}</span>`;
            })
            .join('');

        const matchScoreHtml = isAlternative && room.match_score !== undefined
            ? `<span class="badge match-score-badge"><i class="bi bi-award-fill"></i> Score: ${escapeHtml(room.match_score)}</span>`
            : '';

        const matchReasons = isAlternative && Array.isArray(room.match_reasons)
            ? room.match_reasons
            : [];

        const reasonsHtml = matchReasons.length > 0
            ? `<div class="room-match-reasons">${matchReasons.map((r) => `<span class="match-reason-pill"><i class="bi bi-star-fill"></i> ${escapeHtml(r)}</span>`).join('')}</div>`
            : '';

        return `
            <div class="room-card ${isAlternative ? 'room-card-alternative' : ''}">
                <div class="room-card-header">
                    <div>
                        <h4 class="room-card-title">${escapeHtml(room.name || 'Unnamed Room')}</h4>
                        <span class="room-card-code">${escapeHtml(room.room_code || '')}</span>
                    </div>
                    ${matchScoreHtml}
                </div>
                <div class="room-card-meta">
                    <span class="room-meta-item"><i class="bi bi-geo-alt"></i> ${escapeHtml(room.location_name || 'Main Campus')}</span>
                    ${room.floor ? `<span class="room-meta-item"><i class="bi bi-layers"></i> Floor ${escapeHtml(room.floor)}</span>` : ''}
                    <span class="room-meta-item"><i class="bi bi-people"></i> Cap: <strong>${escapeHtml(room.capacity || 0)}</strong></span>
                </div>
                ${facilitiesHtml ? `<div class="room-card-facilities">${facilitiesHtml}</div>` : ''}
                ${reasonsHtml}
                <div class="room-card-actions">
                    <button type="button" class="btn-select-room-for-booking" data-room-id="${escapeHtml(room.id)}" data-room-name="${escapeHtml(room.name || '')}">
                        <i class="bi bi-calendar-plus"></i> Book This Room
                    </button>
                </div>
            </div>
        `;
    }

    function selectRoomAndOpenBooking(roomId, roomName, startInputVal, endInputVal) {

        closeAvailabilityModalWindow();
        resetBookingForm();

        const roomSelect = document.getElementById('bookingRoom');
        const startInput = document.getElementById('bookingStart');
        const endInput = document.getElementById('bookingEnd');

        if (roomSelect && roomId) {
            roomSelect.value = String(roomId);
        }
        if (startInput && startInputVal) {
            startInput.value = startInputVal;
        }
        if (endInput && endInputVal) {
            endInput.value = endInputVal;
        }

        openBookingModal();

        showAppNotification(
            `Selected ${roomName || 'room'} for booking. Complete the meeting details to proceed.`,
            'success',
            'Room Selected'
        );
    }


    /* ==========================================================================
       API ERROR HANDLING
       ========================================================================== */

    function handleBookingApiError(status, result) {

        if (status === 409) {

            let message =
                result.message ||
                'Room is already booked during this time.';

            if (result.conflicts_count && result.conflicts_count > 0) {
                message = `${result.conflicts_count} recurring occurrence(s) conflict with existing bookings.`;
            }

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
