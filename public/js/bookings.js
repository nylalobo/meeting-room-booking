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
            setupCalendarControls();
            setupAttendanceModal();
            setupVisitorsModal();
            setupResourcesModal();
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

    // Calendar UI State (Milestone 4)
    let currentCalendarDate = new Date();
    let currentCalendarView = 'month'; // 'month' | 'week' | 'day'
    let calendarEvents = [];
    let activeViewMode = 'list'; // 'list' | 'calendar'
    let currentSelectedCalendarEvent = null;

    let currentBookingsPage = 1;
    let currentBookingsPerPage = 10;
    let bookingsPaginationMeta = { page: 1, per_page: 10, total: 0, total_pages: 1 };
    let bookingSearchDebounceTimer = null;


    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadBookingsData() {
        const loading = document.getElementById('bookingsLoading');
        const error = document.getElementById('bookingsError');
        const empty = document.getElementById('bookingsEmpty');
        const tableWrapper = document.getElementById('bookingsTableWrapper');

        try {
            loading?.classList.remove('d-none');
            error?.classList.add('d-none');
            empty?.classList.add('d-none');
            tableWrapper?.classList.add('d-none');

            const [
                roomsRes,
                usersRes
            ] = await Promise.all([
                fetch('/api/rooms?all=1'),
                fetch('/api/users?all=1')
            ]);

            if (!roomsRes.ok || !usersRes.ok) {
                throw new Error('Failed to load booking dependencies.');
            }

            const [
                roomsResult,
                usersResult
            ] = await Promise.all([
                roomsRes.json(),
                usersRes.json()
            ]);

            allRooms = roomsResult.data || [];
            allUsers = usersResult.data || [];

            roomsMap = new Map(
                allRooms.map((r) => [Number(r.id), r])
            );

            usersMap = new Map(
                allUsers.map((u) => [Number(u.id), u])
            );

            populateFilterDropdowns();
            populateModalDropdowns();

            updatePendingApprovalsCount();

            await fetchBookingsPage();

            if (activeViewMode === 'calendar') {
                fetchCalendarEvents();
            }

        } catch (err) {
            console.error('Error in loadBookingsData:', err);
            loading?.classList.add('d-none');
            tableWrapper?.classList.add('d-none');
            empty?.classList.add('d-none');
            error?.classList.remove('d-none');
        }
    }

    async function fetchBookingsPage() {
        const loading = document.getElementById('bookingsLoading');
        const error = document.getElementById('bookingsError');
        const empty = document.getElementById('bookingsEmpty');
        const tableWrapper = document.getElementById('bookingsTableWrapper');
        const paginationWrapper = document.getElementById('bookingsPagination');

        try {
            if (activeViewMode === 'calendar') {
                paginationWrapper?.classList.add('d-none');
                return;
            }

            loading?.classList.remove('d-none');
            error?.classList.add('d-none');

            const searchInput = document.getElementById('bookingSearch');
            const roomFilter = document.getElementById('bookingRoomFilter');
            const statusFilter = document.getElementById('statusFilter');

            const search = (searchInput?.value || '').trim();
            const roomId = roomFilter?.value || '';
            let status = (statusFilter?.value || '').trim();
            if (pendingApprovalsOnly) {
                status = 'pending';
            }

            const params = new URLSearchParams({
                page: String(currentBookingsPage),
                per_page: String(currentBookingsPerPage),
            });
            if (search) params.append('search', search);
            if (roomId) params.append('room_id', roomId);
            if (status) params.append('status', status);

            const res = await fetch(`/api/bookings?${params.toString()}`);
            if (!res.ok) {
                throw new Error(`Failed to load bookings: ${res.status}`);
            }

            const result = await res.json();
            if (result.status !== 'success') {
                throw new Error(result.message || 'Bookings request failed.');
            }

            allBookings = result.data || [];
            bookingsPaginationMeta = {
                page: result.page || currentBookingsPage,
                per_page: result.per_page || currentBookingsPerPage,
                total: result.total || 0,
                total_pages: result.total_pages || 1,
            };

            loading?.classList.add('d-none');

            if (allBookings.length === 0) {
                tableWrapper?.classList.add('d-none');
                empty?.classList.remove('d-none');
            } else {
                empty?.classList.add('d-none');
                tableWrapper?.classList.remove('d-none');
                renderBookings(allBookings);
            }

            if (typeof window.renderPagination === 'function') {
                window.renderPagination(
                    '#bookingsPagination',
                    bookingsPaginationMeta,
                    (newPage) => {
                        currentBookingsPage = newPage;
                        fetchBookingsPage();
                    },
                    (newPerPage) => {
                        currentBookingsPerPage = newPerPage;
                        currentBookingsPage = 1;
                        fetchBookingsPage();
                    }
                );
            }

        } catch (err) {
            console.error('Failed to fetch bookings page:', err);
            loading?.classList.add('d-none');
            tableWrapper?.classList.add('d-none');
            empty?.classList.add('d-none');
            error?.classList.remove('d-none');
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


    function isSelect2Available() {
        return typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined';
    }

    function setSelect2Option(selector, id, text) {
        if (!isSelect2Available()) {
            const el = document.querySelector(selector);
            if (el && id) el.value = String(id);
            return;
        }
        const $select = $(selector);
        if (!$select.length) return;

        if (!id) {
            $select.val(null).trigger('change');
            return;
        }

        const idStr = String(id);
        if ($select.find(`option[value="${idStr}"]`).length === 0) {
            const newOption = new Option(text || `ID #${id}`, idStr, true, true);
            $select.append(newOption).trigger('change');
        } else {
            if (text) {
                $select.find(`option[value="${idStr}"]`).text(text);
            }
            $select.val(idStr).trigger('change');
        }
    }

    function setupBookingSelect2() {
        if (!isSelect2Available()) return;

        const $modal = $('#bookingModal');

        if (!$('#bookingRoom').hasClass('select2-hidden-accessible')) {
            $('#bookingRoom').select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: 'Search and select room...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/rooms/select2',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term || '',
                            page: params.page || 1,
                            per_page: 20
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results || [],
                            pagination: {
                                more: Boolean(data.pagination && data.pagination.more)
                            }
                        };
                    },
                    cache: true
                }
            });
        }

        if (!$('#bookingUser').hasClass('select2-hidden-accessible')) {
            $('#bookingUser').select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: 'Search organizer by name or email...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/users/select2',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term || '',
                            page: params.page || 1,
                            per_page: 20
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results || [],
                            pagination: {
                                more: Boolean(data.pagination && data.pagination.more)
                            }
                        };
                    },
                    cache: true
                }
            });
        }
    }

    function populateModalDropdowns(selectedRoomId = null, selectedUserId = null) {
        if (isSelect2Available()) {
            if (selectedRoomId) {
                const room = roomsMap.get(Number(selectedRoomId));
                const roomText = room ? (room.room_code ? `${room.name} (${room.room_code})` : room.name) : `Room #${selectedRoomId}`;
                setSelect2Option('#bookingRoom', selectedRoomId, roomText);
            } else {
                setSelect2Option('#bookingRoom', null);
            }

            if (selectedUserId) {
                const user = usersMap.get(Number(selectedUserId));
                const userText = user ? (`${user.first_name || ''} ${user.last_name || ''}`.trim() || user.email || `User #${user.id}`) : `User #${selectedUserId}`;
                setSelect2Option('#bookingUser', selectedUserId, userText);
            } else {
                setSelect2Option('#bookingUser', null);
            }
            return;
        }

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
            row.className = 'hover-lift';

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

                        ${booking.can_view_attendance !== false ? `
                            <button
                                type="button"
                                class="booking-action-btn booking-attendance-btn"
                                data-booking-id="${escapeHtml(booking.id)}"
                                data-booking-title="${escapeHtml(booking.title || 'Untitled Meeting')}"
                                title="View attendance & check-ins"
                                aria-label="View attendance"
                            >
                                <i class="bi bi-person-check"></i>
                            </button>
                        ` : ''}

                        <button
                            type="button"
                            class="booking-action-btn booking-visitors-btn"
                            data-booking-id="${escapeHtml(booking.id)}"
                            data-booking-title="${escapeHtml(booking.title || 'Untitled Meeting')}"
                            title="Manage visitors & guests"
                            aria-label="Manage visitors"
                        >
                            <i class="bi bi-person-badge"></i>
                        </button>

                        <button
                            type="button"
                            class="booking-action-btn booking-resources-btn"
                            data-booking-id="${escapeHtml(booking.id)}"
                            data-booking-title="${escapeHtml(booking.title || 'Untitled Meeting')}"
                            title="Manage resources & equipment"
                            aria-label="Manage resources"
                        >
                            <i class="bi bi-box-seam"></i>
                        </button>

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
        const searchInput = document.getElementById('bookingSearch');
        const roomFilter = document.getElementById('bookingRoomFilter');
        const statusFilter = document.getElementById('statusFilter');

        searchInput?.addEventListener('input', () => {
            clearTimeout(bookingSearchDebounceTimer);
            bookingSearchDebounceTimer = setTimeout(() => {
                currentBookingsPage = 1;
                fetchBookingsPage();
                if (activeViewMode === 'calendar') {
                    fetchCalendarEvents();
                }
            }, 250);
        });

        roomFilter?.addEventListener('change', () => {
            currentBookingsPage = 1;
            fetchBookingsPage();
            if (activeViewMode === 'calendar') {
                fetchCalendarEvents();
            }
        });

        statusFilter?.addEventListener('change', () => {
            if (pendingApprovalsOnly) {
                pendingApprovalsOnly = false;
                document.getElementById('pendingApprovalsBtn')?.classList.remove('active');
            }
            currentBookingsPage = 1;
            fetchBookingsPage();
            if (activeViewMode === 'calendar') {
                fetchCalendarEvents();
            }
        });
    }

    function applyBookingFilters() {
        currentBookingsPage = 1;
        fetchBookingsPage();
        if (activeViewMode === 'calendar') {
            fetchCalendarEvents();
        }
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
        setupSafeTimeSelection();
        setupBookingSelect2();
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

        if (isSelect2Available()) {
            $('#bookingRoom, #bookingUser').trigger('change.select2');
        }

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


    function setupSafeTimeSelection() {
        const startInput = document.getElementById('bookingStart');
        const endInput = document.getElementById('bookingEnd');
        if (!startInput || !endInput) return;

        startInput.addEventListener('change', () => {
            if (!startInput.value) return;
            const sDate = new Date(startInput.value);
            if (isNaN(sDate.getTime())) return;

            const eDate = endInput.value ? new Date(endInput.value) : null;
            if (!eDate || isNaN(eDate.getTime()) || eDate <= sDate) {
                const autoEnd = new Date(sDate.getTime() + 60 * 60 * 1000);
                endInput.value = formatDateTimeForInput(autoEnd);
            }
        });

        endInput.addEventListener('change', () => {
            if (!startInput.value || !endInput.value) return;
            const sDate = new Date(startInput.value);
            const eDate = new Date(endInput.value);
            if (!isNaN(sDate.getTime()) && !isNaN(eDate.getTime()) && eDate <= sDate) {
                showBookingFormError('End time must be after start time.');
            } else {
                hideBookingFormMessages();
            }
        });
    }


    function openCreateBookingFromCalendar(options = {}) {
        resetBookingForm();

        const startInput = document.getElementById('bookingStart');
        const endInput = document.getElementById('bookingEnd');
        const roomSelect = document.getElementById('bookingRoom');
        const roomFilter = document.getElementById('bookingRoomFilter');

        if (options.startDate && startInput) {
            const sStr = formatDateTimeForInput(options.startDate);
            startInput.value = sStr;

            if (options.endDate && endInput) {
                endInput.value = formatDateTimeForInput(options.endDate);
            } else if (endInput) {
                const sDate = new Date(options.startDate);
                if (!isNaN(sDate.getTime())) {
                    const eDate = new Date(sDate.getTime() + 60 * 60 * 1000);
                    endInput.value = formatDateTimeForInput(eDate);
                }
            }
        }

        const targetRoomId = options.roomId || (roomFilter ? roomFilter.value : '');
        if (targetRoomId && roomSelect) {
            roomSelect.value = String(targetRoomId);
            if (isSelect2Available()) {
                const r = roomsMap.get(Number(targetRoomId));
                const rLabel = r ? (r.room_code ? `${r.name} (${r.room_code})` : r.name) : `Room #${targetRoomId}`;
                setSelect2Option('#bookingRoom', targetRoomId, rLabel);
            }
        }

        openBookingModal();
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

        const attendanceButtons =
            document.querySelectorAll(
                '.booking-attendance-btn'
            );

        const visitorButtons =
            document.querySelectorAll(
                '.booking-visitors-btn'
            );

        const resourceButtons =
            document.querySelectorAll(
                '.booking-resources-btn'
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

        attendanceButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = Number(button.dataset.bookingId);
                    const bookingTitle = button.dataset.bookingTitle || '';
                    openAttendanceModal(bookingId, bookingTitle);
                }
            );
        });

        visitorButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = Number(button.dataset.bookingId);
                    const bookingTitle = button.dataset.bookingTitle || '';
                    openVisitorsModal(bookingId, bookingTitle);
                }
            );
        });

        resourceButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const bookingId = Number(button.dataset.bookingId);
                    const bookingTitle = button.dataset.bookingTitle || '';
                    openResourcesModal(bookingId, bookingTitle);
                }
            );
        });
    }


    function findBookingById(id) {

        if (!id) return null;

        if (typeof id === 'object' && id.id) {
            return id;
        }

        let b = allBookings.find(
            (item) => String(item.id) === String(id)
        );
        if (b) return b;

        if (Array.isArray(calendarEvents)) {
            b = calendarEvents.find(
                (item) => String(item.id) === String(id)
            );
            if (b) return b;
        }

        return null;
    }


    /* ==========================================================================
       EDIT BOOKING
       ========================================================================== */

    function editBooking(bookingOrId) {

        const booking = (typeof bookingOrId === 'object' && bookingOrId !== null && bookingOrId.id)
            ? bookingOrId
            : findBookingById(bookingOrId);

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
            if (isSelect2Available()) {
                const roomText = booking.room_code ? `${booking.room_name} (${booking.room_code})` : (booking.room_name || `Room #${booking.room_id}`);
                setSelect2Option('#bookingRoom', booking.room_id, roomText);
            }
        }

        if (userSelect) {
            userSelect.value = String(booking.user_id);
            if (isSelect2Available()) {
                const u = usersMap.get(Number(booking.user_id));
                const userText = booking.organizer_name || (u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : `User #${booking.user_id}`);
                setSelect2Option('#bookingUser', booking.user_id, userText);
            }
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
            modalSubtitle.textContent = booking.is_recurring
                ? 'Update this recurring meeting occurrence.'
                : 'Update meeting room booking details.';
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

            closeBookingModalWindow();

            showAppNotification(
                successMessage,
                'success',
                isEditing ? 'Booking Updated' : (isRecurring ? 'Recurring Series Created' : 'Booking Created')
            );

            await loadBookingsData();

            if (activeViewMode === 'calendar') {
                fetchCalendarEvents();
            }

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

        // Context handover from booking modal or calendar
        const bookingStartVal = document.getElementById('bookingStart')?.value;
        const bookingEndVal = document.getElementById('bookingEnd')?.value;
        const bookingRoomVal = document.getElementById('bookingRoom')?.value;
        const calRoomFilterVal = document.getElementById('bookingRoomFilter')?.value;

        if (bookingStartVal && startInput) {
            startInput.value = bookingStartVal;
            if (bookingEndVal && endInput) {
                endInput.value = bookingEndVal;
            }
        } else if (startInput && !startInput.value) {
            // Set default dates if empty (next top of the hour, duration 1 hour)
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

            const roomToSelect = bookingRoomVal || calRoomFilterVal || currentRoomVal;
            if (roomToSelect) {
                specificRoomSelect.value = String(roomToSelect);
            }
        }

        // Load locations if not loaded
        if (locationSelect && !cachedLocations) {
            try {
                const res = await fetch('/api/locations?all=1');
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
                const res = await fetch('/api/facilities?all=1');
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
            if (isSelect2Available()) {
                setSelect2Option('#bookingRoom', roomId, roomName || `Room #${roomId}`);
            }
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
            successText.innerHTML = `
                <span class="d-inline-flex align-items-center gap-2">
                    <svg class="success-checkmark-svg" style="width: 20px; height: 20px; margin: 0; display: inline-block; vertical-align: middle;" viewBox="0 0 52 52">
                        <circle class="success-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="success-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                    <span>${escapeHtml(message)}</span>
                </span>
            `;
        }

        successBox?.classList.add('d-none');

        showAppNotification(message, 'success');
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

        if (apiDateTime instanceof Date) {
            const pad = (n) => String(n).padStart(2, '0');
            return `${apiDateTime.getFullYear()}-${pad(apiDateTime.getMonth() + 1)}-${pad(apiDateTime.getDate())}T${pad(apiDateTime.getHours())}:${pad(apiDateTime.getMinutes())}`;
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


    /* ==========================================================================
       CALENDAR EXPERIENCE (MILESTONE 4)
       ========================================================================== */

    function setupCalendarControls() {

        const listViewBtn = document.getElementById('listViewBtn');
        const calendarViewBtn = document.getElementById('calendarViewBtn');

        const prevBtn = document.getElementById('calendarPrevBtn');
        const nextBtn = document.getElementById('calendarNextBtn');
        const todayBtn = document.getElementById('calendarTodayBtn');

        const viewMonthBtn = document.getElementById('calendarViewMonthBtn');
        const viewWeekBtn = document.getElementById('calendarViewWeekBtn');
        const viewDayBtn = document.getElementById('calendarViewDayBtn');

        const closeCalDetail = document.getElementById('closeCalDetailModal');
        const closeCalDetailBtn = document.getElementById('calDetailCloseBtn');
        const editCalDetailBtn = document.getElementById('calDetailEditBtn');
        const viewSeriesBtn = document.getElementById('calDetailViewSeriesBtn');

        // View Mode Switcher
        listViewBtn?.addEventListener('click', () => {
            switchToListView();
        });

        calendarViewBtn?.addEventListener('click', () => {
            switchToCalendarView();
        });

        // Initialize view based on URL query parameter (?view=calendar)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('view') === 'calendar') {
            switchToCalendarView();
        }

        // Navigation
        prevBtn?.addEventListener('click', () => {
            navigateCalendar(-1);
        });

        nextBtn?.addEventListener('click', () => {
            navigateCalendar(1);
        });

        todayBtn?.addEventListener('click', () => {
            navigateCalendarToday();
        });

        // View Selectors
        viewMonthBtn?.addEventListener('click', () => {
            setCalendarView('month');
        });

        viewWeekBtn?.addEventListener('click', () => {
            setCalendarView('week');
        });

        viewDayBtn?.addEventListener('click', () => {
            setCalendarView('day');
        });

        // Event Detail Modal
        closeCalDetail?.addEventListener('click', closeCalendarDetailModal);
        closeCalDetailBtn?.addEventListener('click', closeCalendarDetailModal);

        editCalDetailBtn?.addEventListener('click', () => {
            closeCalendarDetailModal();
            if (currentSelectedCalendarEvent) {
                editBooking(currentSelectedCalendarEvent);
            }
        });

        const calDetailAttendanceBtn = document.getElementById('calDetailAttendanceBtn');
        calDetailAttendanceBtn?.addEventListener('click', () => {
            if (currentSelectedCalendarEvent && currentSelectedCalendarEvent.id) {
                const bId = Number(currentSelectedCalendarEvent.id);
                const bTitle = currentSelectedCalendarEvent.title || '';
                closeCalendarDetailModal();
                openAttendanceModal(bId, bTitle);
            }
        });

        const calDetailVisitorsBtn = document.getElementById('calDetailVisitorsBtn');
        calDetailVisitorsBtn?.addEventListener('click', () => {
            if (currentSelectedCalendarEvent && currentSelectedCalendarEvent.id) {
                const bId = Number(currentSelectedCalendarEvent.id);
                const bTitle = currentSelectedCalendarEvent.title || '';
                closeCalendarDetailModal();
                openVisitorsModal(bId, bTitle);
            }
        });

        const calDetailResourcesBtn = document.getElementById('calDetailResourcesBtn');
        calDetailResourcesBtn?.addEventListener('click', () => {
            if (currentSelectedCalendarEvent && currentSelectedCalendarEvent.id) {
                const bId = Number(currentSelectedCalendarEvent.id);
                const bTitle = currentSelectedCalendarEvent.title || '';
                closeCalendarDetailModal();
                openResourcesModal(bId, bTitle, currentSelectedCalendarEvent);
            }
        });

        viewSeriesBtn?.addEventListener('click', () => {
            if (currentSelectedCalendarEvent && currentSelectedCalendarEvent.recurring_group_id) {
                loadSeriesOccurrences(currentSelectedCalendarEvent.recurring_group_id, currentSelectedCalendarEvent.id);
            }
        });

        // Calendar Empty State & Error Retry
        const retryBtn = document.getElementById('calendarRetryBtn');
        retryBtn?.addEventListener('click', () => {
            fetchCalendarEvents();
        });

        const emptyActionBtn = document.getElementById('calendarEmptyActionBtn');
        emptyActionBtn?.addEventListener('click', () => {
            const d = currentCalendarDate || new Date();
            const dIso = formatDateToIso(d);
            openCreateBookingFromCalendar({
                startDate: `${dIso} 09:00:00`,
                endDate: `${dIso} 10:00:00`
            });
        });
    }

    function switchToCalendarView() {
        activeViewMode = 'calendar';
        document.getElementById('bookingsListPanel')?.classList.add('d-none');
        document.getElementById('bookingsCalendarContainer')?.classList.remove('d-none');

        document.getElementById('listViewBtn')?.classList.remove('active');
        document.getElementById('calendarViewBtn')?.classList.add('active');

        fetchCalendarEvents();
    }

    function switchToListView() {
        activeViewMode = 'list';
        document.getElementById('bookingsListPanel')?.classList.remove('d-none');
        document.getElementById('bookingsCalendarContainer')?.classList.add('d-none');

        document.getElementById('listViewBtn')?.classList.add('active');
        document.getElementById('calendarViewBtn')?.classList.remove('active');

        applyBookingFilters();
    }

    function setCalendarView(view) {
        if (!['month', 'week', 'day'].includes(view)) {
            return;
        }

        currentCalendarView = view;

        const viewMonthBtn = document.getElementById('calendarViewMonthBtn');
        const viewWeekBtn = document.getElementById('calendarViewWeekBtn');
        const viewDayBtn = document.getElementById('calendarViewDayBtn');

        viewMonthBtn?.classList.toggle('active', view === 'month');
        viewWeekBtn?.classList.toggle('active', view === 'week');
        viewDayBtn?.classList.toggle('active', view === 'day');

        document.getElementById('calendarMonthView')?.classList.toggle('d-none', view !== 'month');
        document.getElementById('calendarWeekView')?.classList.toggle('d-none', view !== 'week');
        document.getElementById('calendarDayView')?.classList.toggle('d-none', view !== 'day');

        fetchCalendarEvents();
    }

    function navigateCalendar(delta) {
        if (currentCalendarView === 'month') {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
        } else if (currentCalendarView === 'week') {
            currentCalendarDate.setDate(currentCalendarDate.getDate() + (delta * 7));
        } else if (currentCalendarView === 'day') {
            currentCalendarDate.setDate(currentCalendarDate.getDate() + delta);
        }
        fetchCalendarEvents();
    }

    function navigateCalendarToday() {
        currentCalendarDate = new Date();
        fetchCalendarEvents();
    }

    function getCalendarDateRange() {
        const year = currentCalendarDate.getFullYear();
        const month = currentCalendarDate.getMonth();
        const day = currentCalendarDate.getDate();

        if (currentCalendarView === 'month') {
            const monthStart = new Date(year, month, 1);
            const monthEnd = new Date(year, month + 1, 0);

            const startDow = monthStart.getDay() === 0 ? 7 : monthStart.getDay();
            const start = new Date(monthStart);
            start.setDate(monthStart.getDate() - (startDow - 1));
            start.setHours(0, 0, 0, 0);

            const endDow = monthEnd.getDay() === 0 ? 7 : monthEnd.getDay();
            const end = new Date(monthEnd);
            end.setDate(monthEnd.getDate() + (7 - endDow));
            end.setHours(23, 59, 59, 999);

            const diffDays = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
            if (diffDays < 35) {
                end.setDate(end.getDate() + 7);
                end.setHours(23, 59, 59, 999);
            }

            return {
                start: start,
                end: end,
                startSql: formatDateTimeToSql(start),
                endSql: formatDateTimeToSql(end)
            };
        }

        if (currentCalendarView === 'week') {
            const currentDow = currentCalendarDate.getDay() === 0 ? 7 : currentCalendarDate.getDay();
            const start = new Date(year, month, day);
            start.setDate(day - (currentDow - 1));
            start.setHours(0, 0, 0, 0);

            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            end.setHours(23, 59, 59, 999);

            return {
                start: start,
                end: end,
                startSql: formatDateTimeToSql(start),
                endSql: formatDateTimeToSql(end)
            };
        }

        // Day view
        const start = new Date(year, month, day, 0, 0, 0, 0);
        const end = new Date(year, month, day, 23, 59, 59, 999);

        return {
            start: start,
            end: end,
            startSql: formatDateTimeToSql(start),
            endSql: formatDateTimeToSql(end)
        };
    }

    function formatDateTimeToSql(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const h = String(d.getHours()).padStart(2, '0');
        const min = String(d.getMinutes()).padStart(2, '0');
        const s = String(d.getSeconds()).padStart(2, '0');
        return `${y}-${m}-${day} ${h}:${min}:${s}`;
    }

    function formatDateToIso(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function updateCalendarHeading(range) {
        const headingEl = document.getElementById('calendarHeading');
        if (!headingEl) return;

        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        if (currentCalendarView === 'month') {
            headingEl.textContent = `${months[currentCalendarDate.getMonth()]} ${currentCalendarDate.getFullYear()}`;
            return;
        }

        if (currentCalendarView === 'week') {
            const s = range.start;
            const e = range.end;

            if (s.getFullYear() === e.getFullYear()) {
                if (s.getMonth() === e.getMonth()) {
                    headingEl.textContent = `${shortMonths[s.getMonth()]} ${s.getDate()} – ${e.getDate()}, ${s.getFullYear()}`;
                } else {
                    headingEl.textContent = `${shortMonths[s.getMonth()]} ${s.getDate()} – ${shortMonths[e.getMonth()]} ${e.getDate()}, ${s.getFullYear()}`;
                }
            } else {
                headingEl.textContent = `${shortMonths[s.getMonth()]} ${s.getDate()}, ${s.getFullYear()} – ${shortMonths[e.getMonth()]} ${e.getDate()}, ${e.getFullYear()}`;
            }
            return;
        }

        // Day view
        const dayName = daysOfWeek[currentCalendarDate.getDay()];
        headingEl.textContent = `${dayName}, ${months[currentCalendarDate.getMonth()]} ${currentCalendarDate.getDate()}, ${currentCalendarDate.getFullYear()}`;
    }

    async function fetchCalendarEvents() {
        if (activeViewMode !== 'calendar') {
            return;
        }

        const loadingOverlay = document.getElementById('calendarLoading');
        const emptyNotice = document.getElementById('calendarEmpty');
        const errorNotice = document.getElementById('calendarError');

        loadingOverlay?.classList.remove('d-none');
        emptyNotice?.classList.add('d-none');
        errorNotice?.classList.add('d-none');

        const range = getCalendarDateRange();
        updateCalendarHeading(range);

        const roomFilter = document.getElementById('bookingRoomFilter')?.value || '';
        let statusFilter = document.getElementById('statusFilter')?.value || '';
        if (pendingApprovalsOnly) {
            statusFilter = 'pending';
        }

        let query = `start=${encodeURIComponent(range.startSql)}&end=${encodeURIComponent(range.endSql)}`;
        if (roomFilter) {
            query += `&room_id=${encodeURIComponent(roomFilter)}`;
        }
        if (statusFilter) {
            query += `&status=${encodeURIComponent(statusFilter)}`;
        }

        try {
            const response = await fetch(`/api/bookings/calendar?${query}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                const userErrMsg = 'Unable to load calendar bookings. Please try again.';
                showAppNotification(userErrMsg, 'error', 'Calendar Error');
                calendarEvents = [];

                emptyNotice?.classList.add('d-none');
                if (errorNotice) {
                    const errorMsgEl = document.getElementById('calendarErrorMessage');
                    if (errorMsgEl) errorMsgEl.textContent = userErrMsg;
                    errorNotice.classList.remove('d-none');
                }
            } else {
                calendarEvents = result.data || [];
                errorNotice?.classList.add('d-none');

                if (calendarEvents.length === 0 && emptyNotice) {
                    const hasActiveFilter = Boolean(roomFilter || (statusFilter && statusFilter !== '') || pendingApprovalsOnly);
                    const emptyMsgEl = document.getElementById('calendarEmptyMessage');
                    if (emptyMsgEl) {
                        emptyMsgEl.textContent = hasActiveFilter
                            ? 'No bookings match your current filters.'
                            : 'No bookings scheduled in this timeframe.';
                    }
                    emptyNotice.classList.remove('d-none');
                }
            }

            renderCalendar(range);

        } catch (err) {
            const userErrMsg = 'Unable to load calendar bookings. Please try again.';
            showAppNotification(userErrMsg, 'error', 'Calendar Error');
            calendarEvents = [];

            emptyNotice?.classList.add('d-none');
            if (errorNotice) {
                const errorMsgEl = document.getElementById('calendarErrorMessage');
                if (errorMsgEl) errorMsgEl.textContent = userErrMsg;
                errorNotice.classList.remove('d-none');
            }
            renderCalendar(range);
        } finally {
            loadingOverlay?.classList.add('d-none');
        }
    }

    function renderCalendar(range) {
        if (currentCalendarView === 'month') {
            renderMonthView(range);
        } else if (currentCalendarView === 'week') {
            renderWeekView(range);
        } else if (currentCalendarView === 'day') {
            renderDayView(range);
        }
    }

    function renderMonthView(range) {
        const container = document.getElementById('calendarMonthView');
        if (!container) return;

        container.innerHTML = '';

        const grid = document.createElement('div');
        grid.className = 'calendar-month-grid';

        const dayHeaders = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        dayHeaders.forEach((name) => {
            const headerCell = document.createElement('div');
            headerCell.className = 'calendar-day-header';
            headerCell.textContent = name;
            grid.appendChild(headerCell);
        });

        const todayStr = formatDateToIso(new Date());
        const currentMonthIdx = currentCalendarDate.getMonth();

        const iterDate = new Date(range.start);
        while (iterDate <= range.end) {
            const cellDate = new Date(iterDate);
            const dateIso = formatDateToIso(cellDate);
            const isToday = (dateIso === todayStr);
            const isOtherMonth = (cellDate.getMonth() !== currentMonthIdx);

            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell' +
                (isOtherMonth ? ' is-other-month' : '') +
                (isToday ? ' is-today' : '');
            cell.setAttribute('role', 'button');
            cell.setAttribute('tabindex', '0');
            cell.title = `Click to book meeting on ${dateIso}`;

            cell.addEventListener('click', () => {
                openCreateBookingFromCalendar({
                    startDate: `${dateIso} 09:00:00`,
                    endDate: `${dateIso} 10:00:00`
                });
            });

            cell.addEventListener('keydown', (e) => {
                if (e.target === cell && (e.key === 'Enter' || e.key === ' ')) {
                    e.preventDefault();
                    openCreateBookingFromCalendar({
                        startDate: `${dateIso} 09:00:00`,
                        endDate: `${dateIso} 10:00:00`
                    });
                }
            });

            const cellTop = document.createElement('div');
            cellTop.className = 'calendar-day-cell-top';

            const numSpan = document.createElement('span');
            numSpan.className = 'calendar-day-number';
            numSpan.textContent = cellDate.getDate();
            cellTop.appendChild(numSpan);
            cell.appendChild(cellTop);

            const eventsContainer = document.createElement('div');
            eventsContainer.className = 'calendar-events-container';

            // Find events occurring on this date
            const dayEvents = calendarEvents.filter((ev) => {
                const evStartDate = (ev.start_time || ev.start || '').slice(0, 10);
                const evEndDate = (ev.end_time || ev.end || '').slice(0, 10);
                return evStartDate <= dateIso && evEndDate >= dateIso;
            });

            const maxVisible = 3;
            const visibleEvents = dayEvents.slice(0, maxVisible);
            const overflowCount = dayEvents.length - maxVisible;

            visibleEvents.forEach((ev) => {
                const pill = document.createElement('div');
                pill.className = `calendar-event-pill event-status-${escapeHtml(String(ev.status || 'pending').toLowerCase())}`;
                pill.setAttribute('role', 'button');
                pill.setAttribute('tabindex', '0');
                pill.title = `${ev.title || 'Meeting'} (${formatEventTimeRange(ev.start_time, ev.end_time)})`;

                const timeComp = parseDateTimeComponents(ev.start_time);
                const timeText = timeComp ? formatTimeForDisplay(timeComp.hours, timeComp.minutes) : '';

                let recurringHtml = '';
                if (ev.is_recurring) {
                    recurringHtml = `<i class="bi bi-repeat event-pill-recurring" title="Recurring Series"></i>`;
                }

                pill.innerHTML = `
                    <span class="event-pill-time">${escapeHtml(timeText)}</span>
                    <span class="event-pill-title">${escapeHtml(ev.title || 'Untitled Meeting')}</span>
                    ${recurringHtml}
                `;

                pill.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openCalendarEventDetails(ev);
                });
                pill.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openCalendarEventDetails(ev);
                    }
                });

                eventsContainer.appendChild(pill);
            });

            if (overflowCount > 0) {
                const moreBtn = document.createElement('div');
                moreBtn.className = 'calendar-more-pill';
                moreBtn.setAttribute('role', 'button');
                moreBtn.setAttribute('tabindex', '0');
                moreBtn.textContent = `+${overflowCount} more`;
                moreBtn.title = `View all ${dayEvents.length} events for ${dateIso}`;

                moreBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    currentCalendarDate = new Date(cellDate);
                    setCalendarView('day');
                });
                moreBtn.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        currentCalendarDate = new Date(cellDate);
                        setCalendarView('day');
                    }
                });

                eventsContainer.appendChild(moreBtn);
            }

            cell.appendChild(eventsContainer);
            grid.appendChild(cell);

            iterDate.setDate(iterDate.getDate() + 1);
        }

        container.appendChild(grid);
    }

    function renderWeekView(range) {
        const container = document.getElementById('calendarWeekView');
        if (!container) return;

        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'calendar-time-grid-wrapper';

        const weekGrid = document.createElement('div');
        weekGrid.className = 'calendar-week-grid';

        // Week Header Row
        const headerRow = document.createElement('div');
        headerRow.className = 'calendar-week-header-row';

        const gutterHeader = document.createElement('div');
        gutterHeader.className = 'calendar-gutter-header';
        headerRow.appendChild(gutterHeader);

        const todayStr = formatDateToIso(new Date());
        const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        const weekDays = [];
        const iter = new Date(range.start);
        for (let i = 0; i < 7; i++) {
            const d = new Date(iter);
            weekDays.push(d);
            const dIso = formatDateToIso(d);
            const isToday = (dIso === todayStr);

            const colHeader = document.createElement('div');
            colHeader.className = 'calendar-week-col-header' + (isToday ? ' is-today' : '');

            colHeader.innerHTML = `
                <span class="cal-col-day-name">${dayNames[i]}</span>
                <span class="cal-col-day-num">${d.getDate()}</span>
            `;

            headerRow.appendChild(colHeader);
            iter.setDate(iter.getDate() + 1);
        }
        weekGrid.appendChild(headerRow);

        // Time Body
        const timeBody = document.createElement('div');
        timeBody.className = 'calendar-time-body';

        // Time Gutter (07:00 to 21:00)
        const gutter = document.createElement('div');
        gutter.className = 'calendar-time-gutter';

        for (let h = 7; h <= 21; h++) {
            const slot = document.createElement('div');
            slot.className = 'calendar-time-gutter-slot';
            slot.textContent = formatTimeForDisplay(h, 0);
            gutter.appendChild(slot);
        }
        timeBody.appendChild(gutter);

        // Week Days Columns Container
        const daysContainer = document.createElement('div');
        daysContainer.className = 'calendar-week-days-container';

        weekDays.forEach((d) => {
            const dIso = formatDateToIso(d);
            const isToday = (dIso === todayStr);

            const dayCol = document.createElement('div');
            dayCol.className = 'calendar-day-col' + (isToday ? ' is-today' : '');

            // Background hour lines
            for (let h = 7; h <= 21; h++) {
                const hourLine = document.createElement('div');
                hourLine.className = 'calendar-hour-line';
                hourLine.innerHTML = `<div class="calendar-half-hour-line"></div>`;
                hourLine.setAttribute('role', 'button');
                hourLine.setAttribute('tabindex', '0');
                const padH = String(h).padStart(2, '0');
                const nextH = String(h + 1).padStart(2, '0');
                hourLine.title = `Click to book meeting at ${padH}:00`;
                hourLine.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openCreateBookingFromCalendar({
                        startDate: `${dIso} ${padH}:00:00`,
                        endDate: `${dIso} ${nextH}:00:00`
                    });
                });
                hourLine.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        e.stopPropagation();
                        openCreateBookingFromCalendar({
                            startDate: `${dIso} ${padH}:00:00`,
                            endDate: `${dIso} ${nextH}:00:00`
                        });
                    }
                });
                dayCol.appendChild(hourLine);
            }

            // Events on this day
            const colEvents = calendarEvents.filter((ev) => {
                const evStart = (ev.start_time || ev.start || '').slice(0, 10);
                return evStart === dIso;
            });

            // Layout overlapping events
            const positionedEvents = calculateEventPositions(colEvents);
            positionedEvents.forEach((item) => {
                const ev = item.event;
                const evCard = document.createElement('div');
                evCard.className = `calendar-grid-event event-status-${escapeHtml(String(ev.status || 'pending').toLowerCase())}`;
                evCard.setAttribute('role', 'button');
                evCard.setAttribute('tabindex', '0');

                evCard.style.top = `${item.top}px`;
                evCard.style.height = `${item.height}px`;
                evCard.style.left = `${item.left}%`;
                evCard.style.width = `${item.width}%`;

                const timeStr = formatEventTimeRange(ev.start_time, ev.end_time);

                let recurringBadge = '';
                if (ev.is_recurring) {
                    recurringBadge = `<i class="bi bi-repeat" title="Recurring Series"></i>`;
                }

                evCard.innerHTML = `
                    <div class="calendar-grid-event-title">${escapeHtml(ev.title || 'Meeting')}</div>
                    <div class="calendar-grid-event-meta">
                        <span>${escapeHtml(timeStr)}</span>
                        ${recurringBadge}
                    </div>
                `;

                evCard.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openCalendarEventDetails(ev);
                });
                evCard.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openCalendarEventDetails(ev);
                    }
                });

                dayCol.appendChild(evCard);
            });

            daysContainer.appendChild(dayCol);
        });

        timeBody.appendChild(daysContainer);
        weekGrid.appendChild(timeBody);
        wrapper.appendChild(weekGrid);
        container.appendChild(wrapper);
    }

    function renderDayView(range) {
        const container = document.getElementById('calendarDayView');
        if (!container) return;

        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'calendar-time-grid-wrapper';

        const dayGrid = document.createElement('div');
        dayGrid.className = 'calendar-day-grid';

        const d = currentCalendarDate;
        const dIso = formatDateToIso(d);
        const todayStr = formatDateToIso(new Date());
        const isToday = (dIso === todayStr);

        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        // Header Row
        const headerRow = document.createElement('div');
        headerRow.className = 'calendar-day-header-row';

        const gutterHeader = document.createElement('div');
        gutterHeader.className = 'calendar-gutter-header';
        headerRow.appendChild(gutterHeader);

        const colHeader = document.createElement('div');
        colHeader.className = 'calendar-day-col-header-single';
        colHeader.innerHTML = `
            <span class="cal-single-day-name">${daysOfWeek[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}</span>
            ${isToday ? '<span class="cal-single-day-badge">Today</span>' : ''}
        `;
        headerRow.appendChild(colHeader);
        dayGrid.appendChild(headerRow);

        // Body
        const timeBody = document.createElement('div');
        timeBody.className = 'calendar-time-body';

        const gutter = document.createElement('div');
        gutter.className = 'calendar-time-gutter';

        for (let h = 7; h <= 21; h++) {
            const slot = document.createElement('div');
            slot.className = 'calendar-time-gutter-slot';
            slot.textContent = formatTimeForDisplay(h, 0);
            gutter.appendChild(slot);
        }
        timeBody.appendChild(gutter);

        const daysContainer = document.createElement('div');
        daysContainer.className = 'calendar-week-days-container';

        const dayCol = document.createElement('div');
        dayCol.className = 'calendar-day-col' + (isToday ? ' is-today' : '');

        for (let h = 7; h <= 21; h++) {
            const hourLine = document.createElement('div');
            hourLine.className = 'calendar-hour-line';
            hourLine.innerHTML = `<div class="calendar-half-hour-line"></div>`;
            hourLine.setAttribute('role', 'button');
            hourLine.setAttribute('tabindex', '0');
            const padH = String(h).padStart(2, '0');
            const nextH = String(h + 1).padStart(2, '0');
            hourLine.title = `Click to book meeting at ${padH}:00`;
            hourLine.addEventListener('click', (e) => {
                e.stopPropagation();
                openCreateBookingFromCalendar({
                    startDate: `${dIso} ${padH}:00:00`,
                    endDate: `${dIso} ${nextH}:00:00`
                });
            });
            hourLine.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    openCreateBookingFromCalendar({
                        startDate: `${dIso} ${padH}:00:00`,
                        endDate: `${dIso} ${nextH}:00:00`
                    });
                }
            });
            dayCol.appendChild(hourLine);
        }

        const dayEvents = calendarEvents.filter((ev) => {
            const evStart = (ev.start_time || ev.start || '').slice(0, 10);
            return evStart === dIso;
        });

        const positionedEvents = calculateEventPositions(dayEvents);
        positionedEvents.forEach((item) => {
            const ev = item.event;
            const evCard = document.createElement('div');
            evCard.className = `calendar-grid-event event-status-${escapeHtml(String(ev.status || 'pending').toLowerCase())}`;
            evCard.setAttribute('role', 'button');
            evCard.setAttribute('tabindex', '0');

            evCard.style.top = `${item.top}px`;
            evCard.style.height = `${item.height}px`;
            evCard.style.left = `${item.left}%`;
            evCard.style.width = `${item.width}%`;

            const timeStr = formatEventTimeRange(ev.start_time, ev.end_time);

            let recurringBadge = '';
            if (ev.is_recurring) {
                recurringBadge = `<span class="badge bg-indigo-subtle text-indigo" style="font-size: 10px; margin-left: 5px;"><i class="bi bi-repeat"></i> Recurring</span>`;
            }

            evCard.innerHTML = `
                <div class="calendar-grid-event-title">${escapeHtml(ev.title || 'Meeting')}</div>
                <div class="calendar-grid-event-meta">
                    <span><i class="bi bi-clock"></i> ${escapeHtml(timeStr)}</span>
                    <span><i class="bi bi-door-open"></i> ${escapeHtml(ev.room_name || '')}</span>
                    ${recurringBadge}
                </div>
            `;

            evCard.addEventListener('click', (e) => {
                e.stopPropagation();
                openCalendarEventDetails(ev);
            });
            evCard.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openCalendarEventDetails(ev);
                }
            });

            dayCol.appendChild(evCard);
        });

        daysContainer.appendChild(dayCol);
        timeBody.appendChild(daysContainer);
        dayGrid.appendChild(timeBody);
        wrapper.appendChild(dayGrid);
        container.appendChild(wrapper);
    }

    function calculateEventPositions(events) {
        if (!events || events.length === 0) {
            return [];
        }

        const slotHeight = 50; // 50px per hour
        const startHourOfDay = 7; // Grid starts at 07:00

        const items = events.map((ev) => {
            const sComp = parseDateTimeComponents(ev.start_time);
            const eComp = parseDateTimeComponents(ev.end_time);

            const sHours = sComp ? (sComp.hours + sComp.minutes / 60) : 9;
            const eHours = eComp ? (eComp.hours + eComp.minutes / 60) : (sHours + 1);

            const clampedStart = Math.max(7, Math.min(21, sHours));
            const clampedEnd = Math.max(clampedStart + 0.33, Math.min(22, eHours));

            const top = (clampedStart - startHourOfDay) * slotHeight;
            const height = Math.max(26, (clampedEnd - clampedStart) * slotHeight);

            return {
                event: ev,
                top: top,
                height: height,
                bottom: top + height,
                startVal: clampedStart,
                endVal: clampedEnd,
                colIndex: 0,
                totalCols: 1
            };
        });

        // Sort by startVal ASC, then by duration DESC
        items.sort((a, b) => a.startVal - b.startVal || (b.endVal - b.startVal) - (a.endVal - a.startVal));

        // Group into clusters of overlapping events
        const clusters = [];
        let currentCluster = [];
        let clusterEnd = -1;

        items.forEach((item) => {
            if (currentCluster.length === 0) {
                currentCluster.push(item);
                clusterEnd = item.endVal;
            } else if (item.startVal < clusterEnd) {
                currentCluster.push(item);
                clusterEnd = Math.max(clusterEnd, item.endVal);
            } else {
                clusters.push(currentCluster);
                currentCluster = [item];
                clusterEnd = item.endVal;
            }
        });
        if (currentCluster.length > 0) {
            clusters.push(currentCluster);
        }

        // For each cluster, assign columns
        clusters.forEach((cluster) => {
            const columns = [];
            cluster.forEach((item) => {
                let placed = false;
                for (let c = 0; c < columns.length; c++) {
                    if (item.startVal >= columns[c]) {
                        item.colIndex = c;
                        columns[c] = item.endVal;
                        placed = true;
                        break;
                    }
                }
                if (!placed) {
                    item.colIndex = columns.length;
                    columns.push(item.endVal);
                }
            });

            const numCols = Math.max(1, columns.length);
            cluster.forEach((item) => {
                item.totalCols = numCols;
                item.left = (item.colIndex / numCols) * 100;
                item.width = (100 / numCols) - 1.5;
            });
        });

        return items;
    }

    function openCalendarEventDetails(ev) {
        currentSelectedCalendarEvent = ev;

        const modal = document.getElementById('calendarEventDetailModal');
        if (!modal) return;

        // Title
        const titleEl = document.getElementById('calDetailTitle');
        if (titleEl) {
            titleEl.textContent = ev.title || 'Untitled Meeting';
        }

        // Status Badge
        const statusBadge = document.getElementById('calDetailStatusBadge');
        if (statusBadge) {
            const normStatus = String(ev.status || 'pending').toLowerCase();
            statusBadge.className = `booking-status booking-status-${normStatus}`;
            statusBadge.textContent = normStatus.charAt(0).toUpperCase() + normStatus.slice(1);
        }

        // Date & Time
        const dateText = formatDateForDisplay(ev.start_time);
        const timeText = formatTimeRangeForDisplay(ev.start_time, ev.end_time);
        const dtEl = document.getElementById('calDetailDateTime');
        if (dtEl) {
            dtEl.textContent = `${dateText} • ${timeText}`;
        }

        // Duration calculation
        const sTime = new Date(ev.start_time).getTime();
        const eTime = new Date(ev.end_time).getTime();
        const durEl = document.getElementById('calDetailDuration');
        if (durEl) {
            if (!isNaN(sTime) && !isNaN(eTime) && eTime > sTime) {
                const diffMins = Math.round((eTime - sTime) / 60000);
                const hrs = Math.floor(diffMins / 60);
                const mins = diffMins % 60;
                let durStr = '';
                if (hrs > 0) durStr += `${hrs} hr${hrs > 1 ? 's' : ''} `;
                if (mins > 0 || hrs === 0) durStr += `${mins} min${mins !== 1 ? 's' : ''}`;
                durEl.textContent = `Duration: ${durStr}`;
            } else {
                durEl.textContent = '';
            }
        }

        // Room & Location
        const roomName = ev.room_name || `Room #${ev.room_id}`;
        const roomCode = ev.room_code ? `(${ev.room_code})` : '';
        const roomEl = document.getElementById('calDetailRoom');
        if (roomEl) {
            roomEl.textContent = `${roomName} ${roomCode}`.trim();
        }
        const locEl = document.getElementById('calDetailLocation');
        if (locEl) {
            locEl.textContent = ev.location_name || 'Location unassigned';
        }

        // Organizer
        const orgEl = document.getElementById('calDetailOrganizer');
        if (orgEl) {
            orgEl.textContent = ev.organizer_name || `User #${ev.user_id}`;
        }
        const orgEmailEl = document.getElementById('calDetailOrganizerEmail');
        if (orgEmailEl) {
            orgEmailEl.textContent = ev.organizer_email || '';
        }

        // Description
        const descEl = document.getElementById('calDetailDescription');
        if (descEl) {
            descEl.textContent = ev.description || 'No meeting description provided.';
        }

        // Cancelled Notice Banner
        const cancelledBanner = document.getElementById('calDetailCancelledBanner');
        if (cancelledBanner) {
            if (normStatus === 'cancelled') {
                cancelledBanner.classList.remove('d-none');
            } else {
                cancelledBanner.classList.add('d-none');
            }
        }

        // Rejection Reason Section
        const rejSection = document.getElementById('calDetailRejectionSection');
        const rejReasonEl = document.getElementById('calDetailRejectionReason');
        if (rejSection && rejReasonEl) {
            const reason = (ev.rejection_reason || '').trim();
            if (normStatus === 'rejected' && reason) {
                rejReasonEl.textContent = reason;
                rejSection.classList.remove('d-none');
            } else {
                rejReasonEl.textContent = '';
                rejSection.classList.add('d-none');
            }
        }

        // Recurrence Section
        const recSection = document.getElementById('calDetailRecurrenceSection');
        const metaEl = document.getElementById('calDetailRecurrenceMeta');
        const seriesList = document.getElementById('calDetailSeriesList');
        const seriesContent = document.getElementById('calSeriesListContent');
        if (seriesList) seriesList.classList.add('d-none');
        if (seriesContent) seriesContent.innerHTML = '';

        if (ev.is_recurring && ev.recurring_group_id) {
            recSection?.classList.remove('d-none');
            const pattern = (ev.recurrence_pattern || 'Recurring').charAt(0).toUpperCase() + (ev.recurrence_pattern || '').slice(1);
            const index = ev.recurrence_index || 1;
            const total = ev.recurrence_total || '—';
            if (metaEl) {
                metaEl.textContent = `${pattern} • Occurrence ${index} of ${total}`;
            }
        } else {
            recSection?.classList.add('d-none');
            if (metaEl) {
                metaEl.textContent = '';
            }
        }

        // Calendar Visitors Button Visibility (Phase 4 Feature 4.3)
        const visBtn = document.getElementById('calDetailVisitorsBtn');
        if (visBtn) {
            const normStatus = String(ev.status || 'pending').toLowerCase();
            const currentUser = window.MeetSpaceUser || null;
            let canViewVisitors = false;

            if (currentUser && currentUser.id) {
                if (['Admin', 'Facilities Manager'].includes(currentUser.role_name)) {
                    canViewVisitors = true;
                } else if (Number(ev.user_id) === Number(currentUser.id)) {
                    canViewVisitors = true;
                } else if (currentUser.role_name === 'Manager' && currentUser.department_id && ev.organizer_department_id && Number(currentUser.department_id) === Number(ev.organizer_department_id)) {
                    canViewVisitors = true;
                } else if (ev.can_view_attendance !== false && ev.can_view_attendance !== undefined) {
                    canViewVisitors = Boolean(ev.can_view_attendance);
                } else if (typeof allBookings !== 'undefined' && Array.isArray(allBookings)) {
                    const matchedBooking = allBookings.find(b => Number(b.id) === Number(ev.id));
                    if (matchedBooking && matchedBooking.can_view_attendance !== false) {
                        canViewVisitors = true;
                    }
                }
            }

            if (normStatus === 'cancelled' || normStatus === 'rejected') {
                canViewVisitors = false;
            }

            if (canViewVisitors) {
                visBtn.classList.remove('d-none');
            } else {
                visBtn.classList.add('d-none');
            }
        }

        // Calendar Resources Button Visibility & Summary (Milestone 5)
        const resBtn = document.getElementById('calDetailResourcesBtn');
        const resContent = document.getElementById('calDetailResourcesContent');
        if (resContent) {
            resContent.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">Loading resources...</span>';
        }
        if (resBtn) {
            let canViewResources = false;
            const currentUser = window.MeetSpaceUser || null;

            if (currentUser && currentUser.id) {
                if (['Admin', 'Facilities Manager'].includes(currentUser.role_name)) {
                    canViewResources = true;
                } else if (Number(ev.user_id) === Number(currentUser.id)) {
                    canViewResources = true;
                } else if (currentUser.role_name === 'Manager' && currentUser.department_id && ev.organizer_department_id && Number(currentUser.department_id) === Number(ev.organizer_department_id)) {
                    canViewResources = true;
                } else if (ev.can_view_attendance !== false && ev.can_view_attendance !== undefined) {
                    canViewResources = Boolean(ev.can_view_attendance);
                } else if (typeof allBookings !== 'undefined' && Array.isArray(allBookings)) {
                    const matchedBooking = allBookings.find(b => Number(b.id) === Number(ev.id));
                    if (matchedBooking && matchedBooking.can_view_attendance !== false) {
                        canViewResources = true;
                    }
                }
            }

            if (normStatus === 'cancelled' || normStatus === 'rejected') {
                canViewResources = false;
            }

            if (canViewResources) {
                resBtn.classList.remove('d-none');
                fetchCalendarEventResourcesSummary(Number(ev.id));
            } else {
                resBtn.classList.add('d-none');
                if (resContent) {
                    resContent.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">None assigned</span>';
                }
            }
        }

        modal.classList.remove('d-none');
    }

    function closeCalendarDetailModal() {
        document.getElementById('calendarEventDetailModal')?.classList.add('d-none');
        document.getElementById('calDetailCancelledBanner')?.classList.add('d-none');
        document.getElementById('calDetailRejectionSection')?.classList.add('d-none');
        document.getElementById('calDetailVisitorsBtn')?.classList.add('d-none');
        document.getElementById('calDetailResourcesBtn')?.classList.add('d-none');
        const resContentEl = document.getElementById('calDetailResourcesContent');
        if (resContentEl) {
            resContentEl.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">None assigned</span>';
        }
        const rejReasonEl = document.getElementById('calDetailRejectionReason');
        if (rejReasonEl) rejReasonEl.textContent = '';
        document.getElementById('calDetailRecurrenceSection')?.classList.add('d-none');
        const metaEl = document.getElementById('calDetailRecurrenceMeta');
        if (metaEl) metaEl.textContent = '';
        const seriesList = document.getElementById('calDetailSeriesList');
        if (seriesList) seriesList.classList.add('d-none');
        const seriesContent = document.getElementById('calSeriesListContent');
        if (seriesContent) seriesContent.innerHTML = '';
        currentSelectedCalendarEvent = null;
    }

    async function loadSeriesOccurrences(recurringGroupId, currentEventId) {
        const seriesList = document.getElementById('calDetailSeriesList');
        const seriesLoading = document.getElementById('calSeriesLoading');
        const seriesContent = document.getElementById('calSeriesListContent');

        if (!seriesList || !seriesContent) return;

        seriesList.classList.remove('d-none');
        seriesLoading?.classList.remove('d-none');
        seriesContent.innerHTML = '';

        try {
            const res = await fetch(`/api/bookings/series/${encodeURIComponent(recurringGroupId)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!res.ok || json.status !== 'success') {
                seriesContent.innerHTML = `<div class="cal-series-loading" style="color: #f87171;">Failed to load series details.</div>`;
                return;
            }

            const occurrences = json.data?.occurrences || [];
            if (occurrences.length === 0) {
                seriesContent.innerHTML = `<div class="cal-series-loading">No occurrences found.</div>`;
                return;
            }

            let html = '';
            occurrences.forEach((occ) => {
                const isCurrent = (Number(occ.id) === Number(currentEventId));
                const dateStr = formatDateForDisplay(occ.start_time);
                const timeStr = formatTimeRangeForDisplay(occ.start_time, occ.end_time);
                const statusNorm = String(occ.status || 'pending').toLowerCase();
                const statusLabel = statusNorm.charAt(0).toUpperCase() + statusNorm.slice(1);

                html += `
                    <div class="cal-series-occurrence-row ${isCurrent ? 'is-current' : ''}">
                        <div>
                            <strong>#${occ.recurrence_index}</strong>
                            <span>${escapeHtml(dateStr)} (${escapeHtml(timeStr)})</span>
                            ${isCurrent ? '<span class="badge bg-primary" style="font-size: 9.5px; margin-left: 4px;">Current</span>' : ''}
                        </div>
                        <span class="booking-status booking-status-${escapeHtml(statusNorm)}" style="font-size: 10px; padding: 2px 6px;">
                            ${escapeHtml(statusLabel)}
                        </span>
                    </div>
                `;
            });

            seriesContent.innerHTML = html;

        } catch (err) {
            seriesContent.innerHTML = `<div class="cal-series-loading" style="color: #f87171;">Network error loading series.</div>`;
        } finally {
            seriesLoading?.classList.add('d-none');
        }
    }

    function formatEventTimeRange(startStr, endStr) {
        const sComp = parseDateTimeComponents(startStr);
        const eComp = parseDateTimeComponents(endStr);
        if (!sComp || !eComp) return '';
        const s = formatTimeForDisplay(sComp.hours, sComp.minutes);
        const e = formatTimeForDisplay(eComp.hours, eComp.minutes);
        return `${s} - ${e}`;
    }


    /* ==========================================================================
       ATTENDANCE MANAGEMENT & TRACKING (FEATURE 4.2)
       ========================================================================== */

    let activeAttendanceBookingId = null;

    function setupAttendanceModal() {
        const modal = document.getElementById('attendanceModal');
        const closeBtn = document.getElementById('closeAttendanceModal');
        const closeBtnFooter = document.getElementById('closeAttendanceModalBtn');
        const attRetryBtn = document.getElementById('attendanceRetryBtn');

        if (!modal) return;

        closeBtn?.addEventListener('click', closeAttendanceModalWindow);
        closeBtnFooter?.addEventListener('click', closeAttendanceModalWindow);

        attRetryBtn?.addEventListener('click', () => {
            if (activeAttendanceBookingId) {
                fetchAttendanceData(activeAttendanceBookingId);
            }
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeAttendanceModalWindow();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
                closeAttendanceModalWindow();
            }
        });
    }

    function openAttendanceModal(bookingId, bookingTitle) {
        activeAttendanceBookingId = Number(bookingId);

        const modal = document.getElementById('attendanceModal');
        const loading = document.getElementById('attendanceLoading');
        const error = document.getElementById('attendanceError');
        const content = document.getElementById('attendanceContent');
        const titleEl = document.getElementById('attMeetingTitle');

        if (!modal) return;

        if (titleEl) {
            titleEl.textContent = bookingTitle || 'Loading meeting...';
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        loading?.classList.remove('d-none');
        error?.classList.add('d-none');
        content?.classList.add('d-none');

        fetchAttendanceData(activeAttendanceBookingId);
    }

    function closeAttendanceModalWindow() {
        const modal = document.getElementById('attendanceModal');
        if (!modal) return;

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
        activeAttendanceBookingId = null;
    }

    async function fetchAttendanceData(bookingId) {
        const loading = document.getElementById('attendanceLoading');
        const error = document.getElementById('attendanceError');
        const errorText = document.getElementById('attendanceErrorText');
        const content = document.getElementById('attendanceContent');

        loading?.classList.remove('d-none');
        error?.classList.add('d-none');
        content?.classList.add('d-none');

        try {
            const response = await fetch(`/api/bookings/${bookingId}/attendance`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                loading?.classList.add('d-none');
                error?.classList.remove('d-none');
                if (errorText) {
                    errorText.textContent = result.message || 'Unable to load attendance data for this meeting.';
                }
                return;
            }

            loading?.classList.add('d-none');
            content?.classList.remove('d-none');
            renderAttendanceData(result.data);

        } catch (err) {
            loading?.classList.add('d-none');
            error?.classList.remove('d-none');
            if (errorText) {
                errorText.textContent = 'Network error while loading attendance. Please try again.';
            }
        }
    }

    function renderAttendanceData(data) {
        if (!data || !data.booking) return;

        // Meeting Header Details
        const titleEl = document.getElementById('attMeetingTitle');
        if (titleEl) {
            titleEl.textContent = data.booking.title || 'Untitled Meeting';
        }

        const statusBadge = document.getElementById('attMeetingStatusBadge');
        if (statusBadge) {
            const st = String(data.booking.status || 'pending').toLowerCase();
            statusBadge.className = `booking-status booking-status-${escapeHtml(st)}`;
            statusBadge.textContent = st.charAt(0).toUpperCase() + st.slice(1);
        }

        const roomLocEl = document.getElementById('attRoomLocation');
        if (roomLocEl) {
            const roomText = data.booking.room_code
                ? `${data.booking.room_name} (${data.booking.room_code})`
                : (data.booking.room_name || 'Room');
            const locText = data.booking.location_name ? ` • ${data.booking.location_name}` : '';
            roomLocEl.textContent = roomText + locText;
        }

        const dateTimeEl = document.getElementById('attDateTime');
        if (dateTimeEl) {
            const dStr = formatDateForDisplay(data.booking.start_time);
            const tStr = formatTimeRangeForDisplay(data.booking.start_time, data.booking.end_time);
            dateTimeEl.textContent = `${dStr} • ${tStr}`;
        }

        const orgEl = document.getElementById('attOrganizer');
        if (orgEl) {
            orgEl.textContent = `Organizer: ${data.booking.organizer_name || 'Unknown'}`;
        }

        // Summary Counts
        const summary = data.summary || {};
        const setStat = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = String(val ?? 0);
        };

        setStat('attStatInvited', summary.total_invited);
        setStat('attStatCheckedIn', summary.total_checked_in);
        setStat('attStatCheckedOut', summary.total_checked_out);
        setStat('attStatCurrentlyCheckedIn', summary.currently_checked_in);
        setStat('attStatNotCheckedIn', summary.not_checked_in);
        setStat('attStatDeclined', summary.total_declined);

        // Attendees List
        const attendees = data.attendees || [];
        const tableBody = document.getElementById('attendanceTableBody');
        const emptyState = document.getElementById('attendanceEmpty');
        const tableWrapper = document.getElementById('attendanceTableWrapper');

        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (attendees.length === 0) {
            tableWrapper?.classList.add('d-none');
            emptyState?.classList.remove('d-none');
            return;
        }

        emptyState?.classList.add('d-none');
        tableWrapper?.classList.remove('d-none');

        attendees.forEach((att) => {
            const tr = document.createElement('tr');

            const isVisitor = Boolean(att.is_visitor || att.participant_type === 'visitor');
            const roleRaw = att.participant_type || (isVisitor ? 'visitor' : 'participant');
            const roleLabel = isVisitor ? 'Visitor' : (roleRaw.charAt(0).toUpperCase() + roleRaw.slice(1));

            const respRaw = att.response_status || (isVisitor ? 'expected' : 'pending');
            const respLabel = (isVisitor && respRaw === 'expected') ? 'Registered' : (respRaw.charAt(0).toUpperCase() + respRaw.slice(1));

            const attStatus = att.attendance_status || 'not_checked_in';
            const attStatusLabel = formatAttendanceStatusLabel(attStatus);

            const inTimeStr = att.check_in_time ? formatTimeOnlyForDisplay(att.check_in_time) : '—';
            const outTimeStr = att.check_out_time ? formatTimeOnlyForDisplay(att.check_out_time) : '—';
            const durStr = att.duration_formatted ? escapeHtml(att.duration_formatted) : '—';

            let lateBadge = '';
            if (att.is_late) {
                lateBadge = `<span class="attendance-badge attendance-badge-late" title="Checked in after scheduled start time"><i class="bi bi-clock-history"></i> Late</span>`;
            }

            let subInfo = '';
            if (att.company && att.email) {
                subInfo = `<div class="attendance-user-email">${escapeHtml(att.company)} • ${escapeHtml(att.email)}</div>`;
            } else if (att.company) {
                subInfo = `<div class="attendance-user-email">${escapeHtml(att.company)}</div>`;
            } else if (att.email) {
                subInfo = `<div class="attendance-user-email">${escapeHtml(att.email)}</div>`;
            }

            tr.innerHTML = `
                <td>
                    <div class="attendance-user-name">
                        ${escapeHtml(att.name || 'User')}
                        ${isVisitor ? '<span class="badge attendance-badge-visitor ms-1" style="font-size: 10px; padding: 1px 6px;">Guest</span>' : ''}
                    </div>
                    ${subInfo}
                </td>
                <td>
                    <span class="attendance-badge attendance-badge-${escapeHtml(roleRaw)}">
                        ${escapeHtml(roleLabel)}
                    </span>
                </td>
                <td>
                    <span class="booking-status booking-status-${escapeHtml(respRaw)}">
                        ${escapeHtml(respLabel)}
                    </span>
                </td>
                <td>
                    <span class="attendance-badge attendance-badge-${escapeHtml(attStatus)}">
                        ${escapeHtml(attStatusLabel)}
                    </span>
                    ${lateBadge}
                </td>
                <td>${escapeHtml(inTimeStr)}</td>
                <td>${escapeHtml(outTimeStr)}</td>
                <td>${durStr}</td>
            `;

            tableBody.appendChild(tr);
        });
    }

    function formatAttendanceStatusLabel(status) {
        switch (status) {
            case 'checked_in':
                return 'Checked In';
            case 'checked_out':
                return 'Checked Out';
            case 'auto_completed':
                return 'Auto Completed';
            case 'expected':
                return 'Expected';
            case 'not_checked_in':
                return 'Not Checked In';
            default:
                return String(status || '').replace(/_/g, ' ');
        }
    }

    function formatTimeOnlyForDisplay(dateTimeStr) {
        const comp = parseDateTimeComponents(dateTimeStr);
        if (!comp) return '—';
        return formatTimeForDisplay(comp.hours, comp.minutes);
    }

    function escapeHtml(str) {
        if (typeof window.escapeHtml === 'function') {
            return window.escapeHtml(str);
        }
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ==========================================================================
       VISITORS / GUESTS MANAGEMENT (FEATURE 4.3)
       ========================================================================== */

    let activeVisitorsBookingId = null;
    let currentVisitorsList = [];

    function setupVisitorsModal() {
        const modal = document.getElementById('visitorsModal');
        const closeBtn = document.getElementById('closeVisitorsModal');
        const closeBtnFooter = document.getElementById('closeVisitorsModalBtn');
        const visitorsRetryBtn = document.getElementById('visitorsRetryBtn');
        const toggleFormBtn = document.getElementById('toggleAddVisitorFormBtn');
        const cancelFormHeaderBtn = document.getElementById('cancelVisitorFormBtn');
        const cancelFormBtn = document.getElementById('cancelVisitorBtn');
        const form = document.getElementById('visitorForm');

        if (!modal) return;

        closeBtn?.addEventListener('click', closeVisitorsModalWindow);
        closeBtnFooter?.addEventListener('click', closeVisitorsModalWindow);

        visitorsRetryBtn?.addEventListener('click', () => {
            if (activeVisitorsBookingId) {
                fetchVisitorsData(activeVisitorsBookingId);
            }
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeVisitorsModalWindow();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
                closeVisitorsModalWindow();
            }
        });

        toggleFormBtn?.addEventListener('click', () => {
            const card = document.getElementById('visitorFormCard');
            if (!card) return;
            if (card.classList.contains('d-none')) {
                resetVisitorForm();
                card.classList.remove('d-none');
                document.getElementById('visitorFullName')?.focus();
            } else {
                card.classList.add('d-none');
            }
        });

        cancelFormHeaderBtn?.addEventListener('click', () => {
            document.getElementById('visitorFormCard')?.classList.add('d-none');
            resetVisitorForm();
        });

        cancelFormBtn?.addEventListener('click', () => {
            document.getElementById('visitorFormCard')?.classList.add('d-none');
            resetVisitorForm();
        });

        form?.addEventListener('submit', handleVisitorFormSubmit);
    }

    function openVisitorsModal(bookingId, bookingTitle) {
        activeVisitorsBookingId = Number(bookingId);

        const modal = document.getElementById('visitorsModal');
        const loading = document.getElementById('visitorsLoading');
        const error = document.getElementById('visitorsError');
        const content = document.getElementById('visitorsContent');
        const titleEl = document.getElementById('visMeetingTitle');

        if (!modal) return;

        if (titleEl) {
            titleEl.textContent = bookingTitle || 'Loading meeting...';
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        loading?.classList.remove('d-none');
        error?.classList.add('d-none');
        content?.classList.add('d-none');

        resetVisitorForm();
        document.getElementById('visitorFormCard')?.classList.add('d-none');

        fetchVisitorsData(activeVisitorsBookingId);
    }

    function closeVisitorsModalWindow() {
        const modal = document.getElementById('visitorsModal');
        if (!modal) return;

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
        resetVisitorForm();
        document.getElementById('visitorFormCard')?.classList.add('d-none');
        activeVisitorsBookingId = null;
        currentVisitorsList = [];
    }

    function resetVisitorForm() {
        const form = document.getElementById('visitorForm');
        if (form) form.reset();
        const editId = document.getElementById('visitorEditId');
        if (editId) editId.value = '';
        const title = document.getElementById('visitorFormTitle');
        if (title) title.textContent = 'Register New Visitor';
        const submitBtnText = document.getElementById('submitVisitorBtnText');
        if (submitBtnText) submitBtnText.textContent = 'Save Visitor';
        const err = document.getElementById('visitorFormError');
        if (err) {
            err.textContent = '';
            err.classList.add('d-none');
        }
    }

    async function fetchVisitorsData(bookingId) {
        const loading = document.getElementById('visitorsLoading');
        const error = document.getElementById('visitorsError');
        const errorText = document.getElementById('visitorsErrorText');
        const content = document.getElementById('visitorsContent');

        loading?.classList.remove('d-none');
        error?.classList.add('d-none');
        content?.classList.add('d-none');

        try {
            const response = await fetch(`/api/bookings/${bookingId}/visitors`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                loading?.classList.add('d-none');
                error?.classList.remove('d-none');
                if (errorText) {
                    errorText.textContent = result.message || 'Unable to load visitors for this meeting.';
                }
                return;
            }

            loading?.classList.add('d-none');
            content?.classList.remove('d-none');
            renderVisitorsData(result.data);

        } catch (err) {
            loading?.classList.add('d-none');
            error?.classList.remove('d-none');
            if (errorText) {
                errorText.textContent = 'Network error while loading visitors. Please try again.';
            }
        }
    }

    function renderVisitorsData(data) {
        if (!data || !data.booking) return;

        currentVisitorsList = data.visitors || [];

        // Meeting Header Details
        const titleEl = document.getElementById('visMeetingTitle');
        if (titleEl) {
            titleEl.textContent = data.booking.title || 'Untitled Meeting';
        }

        const statusBadge = document.getElementById('visMeetingStatusBadge');
        if (statusBadge) {
            const st = String(data.booking.status || 'pending').toLowerCase();
            statusBadge.className = `booking-status booking-status-${escapeHtml(st)}`;
            statusBadge.textContent = st.charAt(0).toUpperCase() + st.slice(1);
        }

        const roomLocEl = document.getElementById('visRoomLocation');
        if (roomLocEl) {
            const roomText = data.booking.room_code
                ? `${data.booking.room_name} (${data.booking.room_code})`
                : (data.booking.room_name || 'Room');
            const locText = data.booking.location_name ? ` • ${data.booking.location_name}` : '';
            roomLocEl.textContent = roomText + locText;
        }

        const dateTimeEl = document.getElementById('visDateTime');
        if (dateTimeEl) {
            const dStr = formatDateForDisplay(data.booking.start_time);
            const tStr = formatTimeRangeForDisplay(data.booking.start_time, data.booking.end_time);
            dateTimeEl.textContent = `${dStr} • ${tStr}`;
        }

        const orgEl = document.getElementById('visOrganizer');
        if (orgEl) {
            orgEl.textContent = `Organizer: ${data.booking.organizer_name || 'Unknown'}`;
        }

        // Visitor Count Badge
        const countBadge = document.getElementById('visitorCountBadge');
        if (countBadge) {
            countBadge.textContent = String(currentVisitorsList.length);
        }

        // Toggle add button visibility based on permissions
        const canManage = Boolean(data.can_manage);
        const toggleBtn = document.getElementById('toggleAddVisitorFormBtn');
        if (toggleBtn) {
            if (canManage) {
                toggleBtn.classList.remove('d-none');
            } else {
                toggleBtn.classList.add('d-none');
            }
        }

        // Visitors Table Body
        const tableBody = document.getElementById('visitorsTableBody');
        const emptyState = document.getElementById('visitorsEmpty');
        const tableWrapper = document.getElementById('visitorsTableWrapper');

        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (currentVisitorsList.length === 0) {
            tableWrapper?.classList.add('d-none');
            emptyState?.classList.remove('d-none');
            return;
        }

        emptyState?.classList.add('d-none');
        tableWrapper?.classList.remove('d-none');

        currentVisitorsList.forEach((visitor) => {
            const tr = document.createElement('tr');

            const statusRaw = visitor.status || 'expected';
            const statusLabel = formatVisitorStatusLabel(statusRaw);

            const inTimeStr = visitor.check_in_time ? formatTimeOnlyForDisplay(visitor.check_in_time) : '—';
            const outTimeStr = visitor.check_out_time ? formatTimeOnlyForDisplay(visitor.check_out_time) : '—';
            const durStr = visitor.duration_formatted ? escapeHtml(visitor.duration_formatted) : '—';

            let companyPhone = '';
            if (visitor.company && visitor.phone) {
                companyPhone = `<div>${escapeHtml(visitor.company)}</div><div class="text-muted" style="font-size: 11px;">${escapeHtml(visitor.phone)}</div>`;
            } else if (visitor.company) {
                companyPhone = `<div>${escapeHtml(visitor.company)}</div>`;
            } else if (visitor.phone) {
                companyPhone = `<div>${escapeHtml(visitor.phone)}</div>`;
            } else {
                companyPhone = '—';
            }

            let actionsHtml = '';
            if (canManage) {
                if (statusRaw === 'expected') {
                    actionsHtml = `
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="visitor-action-btn btn-visitor-checkin" data-visitor-id="${visitor.id}" title="Check In Visitor">
                                <i class="bi bi-box-arrow-in-right"></i> Check In
                            </button>
                            <button type="button" class="visitor-action-btn btn-visitor-edit" data-visitor-id="${visitor.id}" title="Edit Visitor">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="visitor-action-btn btn-visitor-delete" data-visitor-id="${visitor.id}" title="Cancel Visitor Registration">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                } else if (statusRaw === 'checked_in') {
                    actionsHtml = `
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="visitor-action-btn btn-visitor-checkout" data-visitor-id="${visitor.id}" title="Check Out Visitor">
                                <i class="bi bi-box-arrow-right"></i> Check Out
                            </button>
                            <button type="button" class="visitor-action-btn btn-visitor-edit" data-visitor-id="${visitor.id}" title="Edit Visitor">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    `;
                } else {
                    actionsHtml = `
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="visitor-action-btn btn-visitor-edit" data-visitor-id="${visitor.id}" title="Edit Visitor Notes">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    `;
                }
            } else {
                actionsHtml = '<span class="text-muted" style="font-size: 12px;">Read only</span>';
            }

            tr.innerHTML = `
                <td>
                    <div class="attendance-user-name">${escapeHtml(visitor.full_name)}</div>
                    <div class="attendance-user-email">${escapeHtml(visitor.email)}</div>
                    ${visitor.notes ? `<div class="text-muted" style="font-size: 11px; margin-top: 2px;"><i class="bi bi-info-circle"></i> ${escapeHtml(visitor.notes)}</div>` : ''}
                </td>
                <td>${companyPhone}</td>
                <td>
                    <span class="attendance-badge attendance-badge-${escapeHtml(statusRaw)}">
                        ${escapeHtml(statusLabel)}
                    </span>
                </td>
                <td>${escapeHtml(inTimeStr)}</td>
                <td>${escapeHtml(outTimeStr)}</td>
                <td>${durStr}</td>
                <td>${actionsHtml}</td>
            `;

            tableBody.appendChild(tr);
        });

        attachVisitorRowActions();
    }

    function formatVisitorStatusLabel(status) {
        switch (status) {
            case 'expected':
                return 'Expected';
            case 'checked_in':
                return 'Checked In';
            case 'checked_out':
                return 'Checked Out';
            case 'cancelled':
                return 'Cancelled';
            default:
                return String(status || '').replace(/_/g, ' ');
        }
    }

    function attachVisitorRowActions() {
        const tableBody = document.getElementById('visitorsTableBody');
        if (!tableBody) return;

        // Check In buttons
        tableBody.querySelectorAll('.btn-visitor-checkin').forEach((btn) => {
            btn.addEventListener('click', () => {
                const vId = Number(btn.dataset.visitorId);
                checkInVisitorAction(vId);
            });
        });

        // Check Out buttons
        tableBody.querySelectorAll('.btn-visitor-checkout').forEach((btn) => {
            btn.addEventListener('click', () => {
                const vId = Number(btn.dataset.visitorId);
                checkOutVisitorAction(vId);
            });
        });

        // Edit buttons
        tableBody.querySelectorAll('.btn-visitor-edit').forEach((btn) => {
            btn.addEventListener('click', () => {
                const vId = Number(btn.dataset.visitorId);
                openEditVisitorForm(vId);
            });
        });

        // Delete / Cancel buttons
        tableBody.querySelectorAll('.btn-visitor-delete').forEach((btn) => {
            btn.addEventListener('click', () => {
                const vId = Number(btn.dataset.visitorId);
                deleteVisitorAction(vId);
            });
        });
    }

    async function checkInVisitorAction(visitorId) {
        if (!activeVisitorsBookingId || !visitorId) return;

        try {
            const response = await fetch(`/api/bookings/${activeVisitorsBookingId}/visitors/${visitorId}/check-in`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                showAppNotification(result.message || 'Unable to check in visitor.', 'error', 'Check-in Error');
                return;
            }

            showAppNotification(result.message || 'Visitor checked in successfully.', 'success', 'Visitor Checked In');
            fetchVisitorsData(activeVisitorsBookingId);

        } catch (err) {
            showAppNotification('Network error while checking in visitor.', 'error', 'Network Error');
        }
    }

    async function checkOutVisitorAction(visitorId) {
        if (!activeVisitorsBookingId || !visitorId) return;

        try {
            const response = await fetch(`/api/bookings/${activeVisitorsBookingId}/visitors/${visitorId}/check-out`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                showAppNotification(result.message || 'Unable to check out visitor.', 'error', 'Check-out Error');
                return;
            }

            showAppNotification(result.message || 'Visitor checked out successfully.', 'success', 'Visitor Checked Out');
            fetchVisitorsData(activeVisitorsBookingId);

        } catch (err) {
            showAppNotification('Network error while checking out visitor.', 'error', 'Network Error');
        }
    }

    function openEditVisitorForm(visitorId) {
        const visitor = currentVisitorsList.find((v) => Number(v.id) === Number(visitorId));
        if (!visitor) return;

        const card = document.getElementById('visitorFormCard');
        if (!card) return;

        document.getElementById('visitorEditId').value = String(visitor.id);
        document.getElementById('visitorFullName').value = visitor.full_name || '';
        document.getElementById('visitorEmail').value = visitor.email || '';
        document.getElementById('visitorCompany').value = visitor.company || '';
        document.getElementById('visitorPhone').value = visitor.phone || '';
        document.getElementById('visitorNotes').value = visitor.notes || '';

        document.getElementById('visitorFormTitle').textContent = 'Edit Visitor';
        document.getElementById('submitVisitorBtnText').textContent = 'Update Visitor';

        const err = document.getElementById('visitorFormError');
        if (err) {
            err.textContent = '';
            err.classList.add('d-none');
        }

        card.classList.remove('d-none');
        document.getElementById('visitorFullName')?.focus();
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function deleteVisitorAction(visitorId) {
        if (!activeVisitorsBookingId || !visitorId) return;

        const visitor = currentVisitorsList.find((v) => Number(v.id) === Number(visitorId));
        const visitorName = visitor ? visitor.full_name : 'this visitor';

        const confirmed = window.confirm(`Are you sure you want to remove ${visitorName} from this meeting?`);
        if (!confirmed) return;

        try {
            const response = await fetch(`/api/bookings/${activeVisitorsBookingId}/visitors/${visitorId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                showAppNotification(result.message || 'Unable to remove visitor.', 'error', 'Visitor Error');
                return;
            }

            showAppNotification(result.message || 'Visitor removed successfully.', 'success', 'Visitor Removed');
            fetchVisitorsData(activeVisitorsBookingId);

        } catch (err) {
            showAppNotification('Network error while removing visitor.', 'error', 'Network Error');
        }
    }

    async function handleVisitorFormSubmit(e) {
        e.preventDefault();
        if (!activeVisitorsBookingId) return;

        const formCard = document.getElementById('visitorFormCard');
        const errEl = document.getElementById('visitorFormError');
        const submitBtn = document.getElementById('submitVisitorBtn');

        const editId = document.getElementById('visitorEditId')?.value.trim();
        const fullName = document.getElementById('visitorFullName')?.value.trim();
        const email = document.getElementById('visitorEmail')?.value.trim();
        const company = document.getElementById('visitorCompany')?.value.trim();
        const phone = document.getElementById('visitorPhone')?.value.trim();
        const notes = document.getElementById('visitorNotes')?.value.trim();

        if (errEl) {
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }

        if (!fullName) {
            if (errEl) {
                errEl.textContent = 'Please enter the visitor\'s full name.';
                errEl.classList.remove('d-none');
            }
            return;
        }

        if (!email) {
            if (errEl) {
                errEl.textContent = 'Please enter a valid email address.';
                errEl.classList.remove('d-none');
            }
            return;
        }

        const isEditing = Boolean(editId);
        const url = isEditing
            ? `/api/bookings/${activeVisitorsBookingId}/visitors/${editId}`
            : `/api/bookings/${activeVisitorsBookingId}/visitors`;
        const method = isEditing ? 'PUT' : 'POST';

        const payload = {
            full_name: fullName,
            email: email,
            company: company || null,
            phone: phone || null,
            notes: notes || null
        };

        try {
            if (submitBtn) submitBtn.disabled = true;

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                if (errEl) {
                    let errMsg = result.message || 'Unable to save visitor.';
                    if (result.errors && typeof result.errors === 'object') {
                        const firstKey = Object.keys(result.errors)[0];
                        if (firstKey) {
                            errMsg = result.errors[firstKey];
                        }
                    }
                    errEl.textContent = errMsg;
                    errEl.classList.remove('d-none');
                }
                return;
            }

            showAppNotification(
                result.message || (isEditing ? 'Visitor updated successfully.' : 'Visitor registered successfully.'),
                'success',
                'Visitor Saved'
            );

            formCard?.classList.add('d-none');
            resetVisitorForm();
            fetchVisitorsData(activeVisitorsBookingId);

        } catch (err) {
            if (errEl) {
                errEl.textContent = 'Network error while saving visitor. Please try again.';
                errEl.classList.remove('d-none');
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }


    /* ==========================================================================
       MEETING RESOURCES & EQUIPMENT MANAGEMENT (Phase 4 Feature 4.4 Milestone 5)
       ========================================================================== */

    let activeResourcesBookingId = null;
    let activeResourcesBooking = null;
    let currentResourcesList = [];

    /**
     * Fetch quick resource count/summary for the calendar event detail modal.
     */
    async function fetchCalendarEventResourcesSummary(bookingId) {
        const resContent = document.getElementById('calDetailResourcesContent');
        if (!resContent) return;

        try {
            const response = await fetch(`/api/bookings/${bookingId}/resources`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                resContent.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">None assigned</span>';
                return;
            }

            const result = await response.json();
            if (result.status === 'success' && result.data && Array.isArray(result.data.resources)) {
                const resources = result.data.resources;
                if (resources.length === 0) {
                    resContent.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">None assigned</span>';
                } else {
                    resContent.innerHTML = resources.map(r => {
                        const statusBadge = formatResourceStatusBadge(r.status);
                        return `<span class="cal-resource-pill"><i class="bi bi-box-seam"></i> ${escapeHtml(r.equipment_name || 'Equipment')} ${statusBadge}</span>`;
                    }).join('');
                }
            } else {
                resContent.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">None assigned</span>';
            }
        } catch (e) {
            resContent.innerHTML = '<span class="cal-resource-none text-muted" style="font-size: 13px; color: var(--color-text-muted, #7b8cae);">None assigned</span>';
        }
    }

    /**
     * Format a resource lifecycle status badge.
     */
    function formatResourceStatusBadge(status) {
        const s = String(status || 'reserved').toLowerCase();
        let badgeClass = 'badge-resource-reserved';
        let label = 'Reserved';

        if (s === 'checked_out') {
            badgeClass = 'badge-resource-checked-out';
            label = 'Checked Out';
        } else if (s === 'returned') {
            badgeClass = 'badge-resource-returned';
            label = 'Returned';
        } else if (s === 'cancelled') {
            badgeClass = 'badge-resource-cancelled';
            label = 'Cancelled';
        }

        return `<span class="${badgeClass}">${escapeHtml(label)}</span>`;
    }

    /**
     * Set up event listeners for the Booking Resources modal.
     */
    function setupResourcesModal() {
        const modal = document.getElementById('bookingResourcesModal');
        const closeBtn = document.getElementById('closeResourcesModal');
        const closeBtnFooter = document.getElementById('closeResourcesModalBtn');
        const resRetryBtn = document.getElementById('resourcesRetryBtn');
        const toggleFormBtn = document.getElementById('toggleAddResourceFormBtn');
        const cancelFormHeaderBtn = document.getElementById('cancelResourceFormHeaderBtn');
        const cancelFormBtn = document.getElementById('cancelResourceBtn');
        const form = document.getElementById('resourceAssignForm');

        closeBtn?.addEventListener('click', closeResourcesModalWindow);
        closeBtnFooter?.addEventListener('click', closeResourcesModalWindow);

        resRetryBtn?.addEventListener('click', () => {
            if (activeResourcesBookingId) {
                fetchResourcesData(activeResourcesBookingId);
            }
        });

        // Close on backdrop click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeResourcesModalWindow();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.classList.contains('d-none')) {
                closeResourcesModalWindow();
            }
        });

        toggleFormBtn?.addEventListener('click', () => {
            const formCard = document.getElementById('resourceFormCard');
            if (formCard) {
                const isHidden = formCard.classList.contains('d-none');
                if (isHidden) {
                    formCard.classList.remove('d-none');
                    loadAvailableEquipmentDropdown();
                } else {
                    formCard.classList.add('d-none');
                    resetResourceForm();
                }
            }
        });

        cancelFormHeaderBtn?.addEventListener('click', () => {
            document.getElementById('resourceFormCard')?.classList.add('d-none');
            resetResourceForm();
        });

        cancelFormBtn?.addEventListener('click', () => {
            document.getElementById('resourceFormCard')?.classList.add('d-none');
            resetResourceForm();
        });

        form?.addEventListener('submit', handleResourceFormSubmit);
    }

    /**
     * Open the Booking Resources modal for a given booking.
     */
    function openResourcesModal(bookingId, bookingTitle, bookingData) {
        activeResourcesBookingId = Number(bookingId);
        activeResourcesBooking = bookingData || findBookingById(bookingId) || null;

        const modal = document.getElementById('bookingResourcesModal');
        const loading = document.getElementById('resourcesLoading');
        const error = document.getElementById('resourcesError');
        const content = document.getElementById('resourcesContent');
        const titleEl = document.getElementById('resMeetingTitle');

        if (!modal) return;

        if (titleEl) {
            titleEl.textContent = bookingTitle || (activeResourcesBooking ? activeResourcesBooking.title : 'Loading meeting...');
        }

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        loading?.classList.remove('d-none');
        error?.classList.add('d-none');
        content?.classList.add('d-none');

        resetResourceForm();
        document.getElementById('resourceFormCard')?.classList.add('d-none');

        fetchResourcesData(activeResourcesBookingId);
    }

    /**
     * Close the Booking Resources modal and reset state.
     */
    function closeResourcesModalWindow() {
        const modal = document.getElementById('bookingResourcesModal');
        if (!modal) return;

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
        resetResourceForm();
        document.getElementById('resourceFormCard')?.classList.add('d-none');
        activeResourcesBookingId = null;
        activeResourcesBooking = null;
        currentResourcesList = [];
    }

    /**
     * Reset the resource assignment form fields and error display.
     */
    function resetResourceForm() {
        const form = document.getElementById('resourceAssignForm');
        if (form) form.reset();
        const errEl = document.getElementById('resourceFormError');
        if (errEl) {
            errEl.textContent = '';
            errEl.innerHTML = '';
            errEl.classList.add('d-none');
        }
        const selectEl = document.getElementById('resourceEquipmentSelect');
        if (selectEl) {
            selectEl.value = '';
        }
    }

    /**
     * Fetch assigned resources from the backend API.
     */
    async function fetchResourcesData(bookingId) {
        const loading = document.getElementById('resourcesLoading');
        const error = document.getElementById('resourcesError');
        const errorText = document.getElementById('resourcesErrorText');
        const content = document.getElementById('resourcesContent');

        loading?.classList.remove('d-none');
        error?.classList.add('d-none');
        content?.classList.add('d-none');

        try {
            // If booking metadata is missing, fetch it in parallel
            let bookingFetchPromise = null;
            if (!activeResourcesBooking || Number(activeResourcesBooking.id) !== Number(bookingId)) {
                bookingFetchPromise = fetch(`/api/bookings/${bookingId}`, {
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.ok ? r.json() : null).catch(() => null);
            }

            const response = await fetch(`/api/bookings/${bookingId}/resources`, {
                headers: { 'Accept': 'application/json' }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                loading?.classList.add('d-none');
                error?.classList.remove('d-none');
                if (errorText) {
                    errorText.textContent = result.message || 'Unable to load resources for this meeting.';
                }
                return;
            }

            if (bookingFetchPromise) {
                const bookingResult = await bookingFetchPromise;
                if (bookingResult && bookingResult.data) {
                    activeResourcesBooking = bookingResult.data;
                }
            }

            loading?.classList.add('d-none');
            content?.classList.remove('d-none');
            renderResourcesData(result.data, activeResourcesBooking);

        } catch (err) {
            loading?.classList.add('d-none');
            error?.classList.remove('d-none');
            if (errorText) {
                errorText.textContent = 'Network error while loading resources. Please try again.';
            }
        }
    }

    /**
     * Render the assigned resources into the modal view.
     */
    function renderResourcesData(data, booking) {
        if (!data) return;

        currentResourcesList = data.resources || [];
        const canManage = Boolean(data.can_manage);

        // Meeting Header Details
        const b = booking || {};
        const titleEl = document.getElementById('resMeetingTitle');
        if (titleEl) {
            titleEl.textContent = b.title || 'Untitled Meeting';
        }

        const statusBadge = document.getElementById('resMeetingStatusBadge');
        if (statusBadge) {
            const st = String(b.status || 'pending').toLowerCase();
            statusBadge.className = `booking-status booking-status-${escapeHtml(st)}`;
            statusBadge.textContent = st.charAt(0).toUpperCase() + st.slice(1);
        }

        const roomLocEl = document.getElementById('resRoomLocation');
        if (roomLocEl) {
            const roomText = b.room_code
                ? `${b.room_name} (${b.room_code})`
                : (b.room_name || 'Room');
            const locText = b.location_name ? ` • ${b.location_name}` : '';
            roomLocEl.textContent = roomText + locText;
        }

        const dateTimeEl = document.getElementById('resDateTime');
        if (dateTimeEl) {
            const dStr = b.start_time ? formatDateForDisplay(b.start_time) : '—';
            const tStr = (b.start_time && b.end_time) ? formatTimeRangeForDisplay(b.start_time, b.end_time) : '—';
            dateTimeEl.textContent = `${dStr} • ${tStr}`;
        }

        const orgEl = document.getElementById('resOrganizer');
        if (orgEl) {
            orgEl.textContent = `Organizer: ${b.organizer_name || 'Unknown'}`;
        }

        // Recurrence Occurrence Banner
        const recurrenceBanner = document.getElementById('resRecurrenceBanner');
        if (recurrenceBanner) {
            if (b.is_recurring || b.recurring_group_id) {
                recurrenceBanner.classList.remove('d-none');
            } else {
                recurrenceBanner.classList.add('d-none');
            }
        }

        // Cancelled / Rejected Warning Banner
        const isCancelledOrRejected = ['cancelled', 'rejected'].includes(String(b.status || '').toLowerCase());
        const cancelledBanner = document.getElementById('resCancelledBanner');
        const cancelledText = document.getElementById('resCancelledText');
        if (cancelledBanner) {
            if (isCancelledOrRejected) {
                cancelledBanner.classList.remove('d-none');
                if (cancelledText) {
                    cancelledText.textContent = `This booking is ${b.status}. Resource assignments cannot be modified.`;
                }
            } else {
                cancelledBanner.classList.add('d-none');
            }
        }

        // Stats Counters
        let countReserved = 0;
        let countCheckedOut = 0;
        let countReturned = 0;

        currentResourcesList.forEach(r => {
            const s = String(r.status || 'reserved').toLowerCase();
            if (s === 'reserved') countReserved++;
            else if (s === 'checked_out') countCheckedOut++;
            else if (s === 'returned') countReturned++;
        });

        const statTotal = document.getElementById('resStatTotal');
        const statReserved = document.getElementById('resStatReserved');
        const statCheckedOut = document.getElementById('resStatCheckedOut');
        const statReturned = document.getElementById('resStatReturned');
        const countBadge = document.getElementById('resourceCountBadge');

        if (statTotal) statTotal.textContent = String(currentResourcesList.length);
        if (statReserved) statReserved.textContent = String(countReserved);
        if (statCheckedOut) statCheckedOut.textContent = String(countCheckedOut);
        if (statReturned) statReturned.textContent = String(countReturned);
        if (countBadge) countBadge.textContent = String(currentResourcesList.length);

        // RBAC: Show/hide "+ Assign Resource" toggle button
        const toggleBtn = document.getElementById('toggleAddResourceFormBtn');
        if (toggleBtn) {
            if (canManage && !isCancelledOrRejected) {
                toggleBtn.classList.remove('d-none');
            } else {
                toggleBtn.classList.add('d-none');
            }
        }

        // Resources Table Body
        const tableBody = document.getElementById('resourcesTableBody');
        const emptyState = document.getElementById('resourcesEmpty');
        const tableWrapper = document.getElementById('resourcesTableWrapper');

        if (!tableBody) return;
        tableBody.innerHTML = '';

        if (currentResourcesList.length === 0) {
            tableWrapper?.classList.add('d-none');
            emptyState?.classList.remove('d-none');
            return;
        }

        emptyState?.classList.add('d-none');
        tableWrapper?.classList.remove('d-none');

        currentResourcesList.forEach((r) => {
            const tr = document.createElement('tr');
            const resStatus = String(r.status || 'reserved').toLowerCase();

            // Format check-in/out info
            let checkoutDisplay = '—';
            if (r.checked_out_at) {
                const coTime = formatDateTimeFullForDisplay ? formatDateTimeFullForDisplay(r.checked_out_at) : r.checked_out_at;
                const coUser = r.checked_out_by_name ? `by ${escapeHtml(r.checked_out_by_name)}` : '';
                checkoutDisplay = `<span>${coTime}</span>${coUser ? `<small class="text-muted d-block" style="font-size: 11px;">${coUser}</small>` : ''}`;
            }

            let returnDisplay = '—';
            if (r.returned_at) {
                const retTime = formatDateTimeFullForDisplay ? formatDateTimeFullForDisplay(r.returned_at) : r.returned_at;
                const retUser = r.returned_to_name ? `to ${escapeHtml(r.returned_to_name)}` : '';
                returnDisplay = `<span>${retTime}</span>${retUser ? `<small class="text-muted d-block" style="font-size: 11px;">${retUser}</small>` : ''}`;
            }

            // Location
            const locationText = escapeHtml(r.location_name || r.default_room_name || '—');

            // Action buttons
            let actionsHtml = '';
            if (canManage && !isCancelledOrRejected) {
                if (resStatus === 'reserved') {
                    actionsHtml = `
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button
                                type="button"
                                class="btn-resource-action btn-resource-checkout"
                                data-resource-id="${r.id}"
                                data-equipment-name="${escapeHtml(r.equipment_name || 'Equipment')}"
                                title="Check out equipment"
                            >
                                <i class="bi bi-box-arrow-up-right"></i> Check Out
                            </button>
                            <button
                                type="button"
                                class="btn-resource-action btn-resource-remove"
                                data-resource-id="${r.id}"
                                data-equipment-name="${escapeHtml(r.equipment_name || 'Equipment')}"
                                title="Remove assignment"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                } else if (resStatus === 'checked_out') {
                    actionsHtml = `
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button
                                type="button"
                                class="btn-resource-action btn-resource-return"
                                data-resource-id="${r.id}"
                                data-equipment-name="${escapeHtml(r.equipment_name || 'Equipment')}"
                                title="Return equipment"
                            >
                                <i class="bi bi-box-arrow-in-down-left"></i> Return
                            </button>
                            <button
                                type="button"
                                class="btn-resource-action btn-resource-remove"
                                disabled
                                title="Cannot remove equipment that is checked out. Return it first."
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                } else if (resStatus === 'returned') {
                    actionsHtml = `
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button
                                type="button"
                                class="btn-resource-action btn-resource-remove"
                                data-resource-id="${r.id}"
                                data-equipment-name="${escapeHtml(r.equipment_name || 'Equipment')}"
                                title="Remove assignment record"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            } else {
                actionsHtml = '<span class="text-muted" style="font-size: 12px; font-style: italic;">View only</span>';
            }

            const modelSerial = [r.model_number ? `Model: ${r.model_number}` : '', r.serial_number ? `SN: ${r.serial_number}` : ''].filter(Boolean).join(' • ');

            tr.innerHTML = `
                <td>
                    <div style="font-weight: 600; color: #f1f5f9;">${escapeHtml(r.equipment_name || 'Equipment')}</div>
                    ${modelSerial ? `<small class="text-muted" style="font-size: 11px;">${escapeHtml(modelSerial)}</small>` : ''}
                </td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                        <span class="badge equip-code-badge">${escapeHtml(r.equipment_code || '—')}</span>
                        <span class="badge badge-equip-category" style="font-size: 10px;">${escapeHtml(r.category || 'General')}</span>
                    </div>
                </td>
                <td>${locationText}</td>
                <td>${formatResourceStatusBadge(r.status)}</td>
                <td>${checkoutDisplay}</td>
                <td>${returnDisplay}</td>
                <td style="max-width: 160px; word-break: break-word;">${r.notes ? escapeHtml(r.notes) : '<span class="text-muted">—</span>'}</td>
                <td>${actionsHtml}</td>
            `;

            tableBody.appendChild(tr);
        });

        // Wire action button listeners
        tableBody.querySelectorAll('.btn-resource-checkout').forEach(btn => {
            btn.addEventListener('click', () => {
                const rid = Number(btn.dataset.resourceId);
                const eqName = btn.dataset.equipmentName || 'Equipment';
                handleResourceCheckout(activeResourcesBookingId, rid, eqName);
            });
        });

        tableBody.querySelectorAll('.btn-resource-return').forEach(btn => {
            btn.addEventListener('click', () => {
                const rid = Number(btn.dataset.resourceId);
                const eqName = btn.dataset.equipmentName || 'Equipment';
                handleResourceReturn(activeResourcesBookingId, rid, eqName);
            });
        });

        tableBody.querySelectorAll('.btn-resource-remove:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                const rid = Number(btn.dataset.resourceId);
                const eqName = btn.dataset.equipmentName || 'Equipment';
                handleResourceDelete(activeResourcesBookingId, rid, eqName);
            });
        });
    }

    /**
     * Load catalog available equipment into the select dropdown.
     */
    async function loadAvailableEquipmentDropdown() {
        const select = document.getElementById('resourceEquipmentSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Loading available equipment...</option>';
        select.disabled = true;

        try {
            const response = await fetch('/api/equipment/availability', {
                headers: { 'Accept': 'application/json' }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();
            select.disabled = false;

            if (!response.ok || result.status !== 'success' || !Array.isArray(result.data)) {
                select.innerHTML = '<option value="">Unable to load available equipment</option>';
                return;
            }

            const items = result.data;
            const assignedIds = new Set(currentResourcesList.map(r => Number(r.equipment_id)));
            const availableItems = items.filter(item => !assignedIds.has(Number(item.id)));

            if (availableItems.length === 0) {
                select.innerHTML = '<option value="">No equipment currently available in catalog</option>';
                return;
            }

            let optionsHtml = '<option value="">Select an equipment item to assign...</option>';
            availableItems.forEach(item => {
                const locStr = item.location_name ? ` [${item.location_name}]` : (item.default_room_name ? ` [Room: ${item.default_room_name}]` : '');
                optionsHtml += `<option value="${item.id}">${escapeHtml(item.name)} (${escapeHtml(item.code || '—')}) - ${escapeHtml(item.category || 'General')}${escapeHtml(locStr)}</option>`;
            });

            select.innerHTML = optionsHtml;

        } catch (e) {
            select.disabled = false;
            select.innerHTML = '<option value="">Network error loading equipment</option>';
        }
    }

    /**
     * Handle submission of the assign resource form.
     */
    async function handleResourceFormSubmit(e) {
        e.preventDefault();
        if (!activeResourcesBookingId) return;

        const select = document.getElementById('resourceEquipmentSelect');
        const notesInput = document.getElementById('resourceNotes');
        const errEl = document.getElementById('resourceFormError');
        const submitBtn = document.getElementById('submitResourceBtn');
        const formCard = document.getElementById('resourceFormCard');

        const equipmentId = select ? select.value : '';
        if (!equipmentId) {
            if (errEl) {
                errEl.innerHTML = '<i class="bi bi-exclamation-circle"></i> Please select an equipment item to assign.';
                errEl.classList.remove('d-none');
            }
            return;
        }

        if (errEl) {
            errEl.innerHTML = '';
            errEl.classList.add('d-none');
        }

        const payload = {
            equipment_id: Number(equipmentId),
            quantity: 1,
            notes: (notesInput?.value || '').trim() || null
        };

        try {
            if (submitBtn) submitBtn.disabled = true;

            const response = await fetch(`/api/bookings/${activeResourcesBookingId}/resources`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            // Conflict handling (HTTP 409)
            if (response.status === 409) {
                if (errEl) {
                    let conflictHtml = `<i class="bi bi-exclamation-triangle-fill" style="color: #fca5a5; font-size: 15px;"></i> <div><strong>Schedule Conflict:</strong> ${escapeHtml(result.message || 'Equipment is reserved for an overlapping booking.')}`;
                    if (result.conflicting_booking) {
                        const cb = result.conflicting_booking;
                        const cbId = cb.booking_id || cb.id || '—';
                        const cbTitle = escapeHtml(cb.booking_title || cb.title || 'Untitled Meeting');
                        const cbTime = (cb.start_time && cb.end_time) ? `${formatDateForDisplay(cb.start_time)} ${formatTimeRangeForDisplay(cb.start_time, cb.end_time)}` : '';
                        conflictHtml += `<br><small style="margin-top: 4px; display: block; color: #fed7aa;">Conflicting Booking #${cbId}: <strong>"${cbTitle}"</strong> (${cbTime})</small>`;
                    }
                    conflictHtml += '</div>';
                    errEl.innerHTML = conflictHtml;
                    errEl.classList.remove('d-none');
                }
                return;
            }

            if (!response.ok || result.status !== 'success') {
                if (errEl) {
                    let errMsg = result.message || 'Unable to assign resource.';
                    if (result.errors && typeof result.errors === 'object') {
                        const firstKey = Object.keys(result.errors)[0];
                        if (firstKey) errMsg = result.errors[firstKey];
                    }
                    errEl.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${escapeHtml(errMsg)}`;
                    errEl.classList.remove('d-none');
                }
                return;
            }

            // Success
            if (typeof showAppNotification === 'function') {
                showAppNotification(result.message || 'Resource assigned successfully.', 'success', 'Equipment Assigned');
            }

            formCard?.classList.add('d-none');
            resetResourceForm();
            fetchResourcesData(activeResourcesBookingId);

        } catch (err) {
            if (errEl) {
                errEl.innerHTML = '<i class="bi bi-exclamation-circle"></i> Network error while assigning equipment. Please try again.';
                errEl.classList.remove('d-none');
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    /**
     * Handle resource checkout action.
     */
    async function handleResourceCheckout(bookingId, resourceId, equipmentName) {
        if (!confirm(`Check out "${equipmentName}" for this meeting?`)) {
            return;
        }

        try {
            const response = await fetch(`/api/bookings/${bookingId}/resources/${resourceId}/checkout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                if (typeof showAppNotification === 'function') {
                    showAppNotification(result.message || 'Unable to check out resource.', 'error', 'Checkout Failed');
                } else {
                    alert(result.message || 'Unable to check out resource.');
                }
                return;
            }

            if (typeof showAppNotification === 'function') {
                showAppNotification(result.message || `Checked out "${equipmentName}".`, 'success', 'Resource Checked Out');
            }

            fetchResourcesData(bookingId);

        } catch (e) {
            if (typeof showAppNotification === 'function') {
                showAppNotification('Network error during checkout.', 'error', 'Error');
            }
        }
    }

    /**
     * Handle resource return action.
     */
    async function handleResourceReturn(bookingId, resourceId, equipmentName) {
        if (!confirm(`Confirm return of "${equipmentName}"?`)) {
            return;
        }

        try {
            const response = await fetch(`/api/bookings/${bookingId}/resources/${resourceId}/return`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                if (typeof showAppNotification === 'function') {
                    showAppNotification(result.message || 'Unable to return resource.', 'error', 'Return Failed');
                } else {
                    alert(result.message || 'Unable to return resource.');
                }
                return;
            }

            if (typeof showAppNotification === 'function') {
                showAppNotification(result.message || `Returned "${equipmentName}".`, 'success', 'Resource Returned');
            }

            fetchResourcesData(bookingId);

        } catch (e) {
            if (typeof showAppNotification === 'function') {
                showAppNotification('Network error during return.', 'error', 'Error');
            }
        }
    }

    /**
     * Handle removing an assigned resource.
     */
    async function handleResourceDelete(bookingId, resourceId, equipmentName) {
        if (!confirm(`Are you sure you want to remove "${equipmentName}" from this meeting?`)) {
            return;
        }

        try {
            const response = await fetch(`/api/bookings/${bookingId}/resources/${resourceId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                if (typeof showAppNotification === 'function') {
                    showAppNotification(result.message || 'Unable to remove resource.', 'error', 'Delete Failed');
                } else {
                    alert(result.message || 'Unable to remove resource.');
                }
                return;
            }

            if (typeof showAppNotification === 'function') {
                showAppNotification(result.message || `Removed "${equipmentName}".`, 'success', 'Resource Removed');
            }

            fetchResourcesData(bookingId);

        } catch (e) {
            if (typeof showAppNotification === 'function') {
                showAppNotification('Network error during removal.', 'error', 'Error');
            }
        }
    }


})();
