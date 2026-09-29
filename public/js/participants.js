/**
 * MeetSpace Enterprise Suite - Meeting-Centric Participants Dashboard JavaScript
 *
 * Handles:
 * - Loading participants, bookings, users, roles, and departments via Promise.all
 * - Accurate dashboard summary metrics calculation
 * - Event/Meeting-centric card rendering where meeting is the primary visual object
 * - Guaranteed Organizer first ordering with distinctive visual hierarchy
 * - Multi-criteria search and filtering (meeting, attendee, status, date, role)
 * - Add/Edit attendee modal with pre-selected meeting context
 * - Participant removal with custom confirmation dialog and toast feedback
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        if (
            document.getElementById('participantsCardsContainer') ||
            document.getElementById('participantsTableBody')
        ) {
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
    let allRoles = [];
    let allDepartments = [];

    let bookingsMap = new Map();
    let usersMap = new Map();
    let rolesMap = new Map();
    let departmentsMap = new Map();

    /* ==========================================================================
       DATA LOADING
       ========================================================================== */

    async function loadParticipantsData() {
        const loading = document.getElementById('participantsLoading');
        const error = document.getElementById('participantsError');
        const empty = document.getElementById('participantsEmpty');
        const container = document.getElementById('participantsCardsContainer');

        try {
            loading?.classList.remove('d-none');
            error?.classList.add('d-none');
            empty?.classList.add('d-none');
            container?.classList.add('d-none');

            const [
                participantsRes,
                bookingsRes,
                usersRes,
                rolesRes,
                departmentsRes
            ] = await Promise.all([
                fetch('/booking-participants'),
                fetch('/api/bookings'),
                fetch('/api/users'),
                fetch('/roles'),
                fetch('/api/departments')
            ]);

            if (
                !participantsRes.ok ||
                !bookingsRes.ok ||
                !usersRes.ok
            ) {
                throw new Error('One or more participant data sources failed to load.');
            }

            const [
                participantsResult,
                bookingsResult,
                usersResult,
                rolesResult,
                departmentsResult
            ] = await Promise.all([
                participantsRes.json(),
                bookingsRes.json(),
                usersRes.json(),
                rolesRes.ok ? rolesRes.json() : Promise.resolve({ data: [] }),
                departmentsRes.ok ? departmentsRes.json() : Promise.resolve({ data: [] })
            ]);

            allParticipants = participantsResult.data || [];
            allBookings = bookingsResult.data || [];
            allUsers = usersResult.data || [];
            allRoles = rolesResult.data || [];
            allDepartments = departmentsResult.data || [];

            bookingsMap = new Map(allBookings.map((b) => [String(b.id), b]));
            usersMap = new Map(allUsers.map((u) => [String(u.id), u]));
            rolesMap = new Map(allRoles.map((r) => [String(r.id), r]));
            departmentsMap = new Map(allDepartments.map((d) => [String(d.id), d]));

            updateSummaryMetrics();
            populateFilterDropdowns();
            populateModalDropdowns();

            loading?.classList.add('d-none');
            applyParticipantFilters();

        } catch (err) {
            console.error('Unable to load participants data:', err);
            loading?.classList.add('d-none');
            container?.classList.add('d-none');
            empty?.classList.add('d-none');
            error?.classList.remove('d-none');
        }
    }

    /* ==========================================================================
       SUMMARY METRICS
       ========================================================================== */

    function updateSummaryMetrics() {
        const totalMeetingsEl = document.getElementById('metricTotalMeetings');
        const totalParticipantsEl = document.getElementById('metricTotalParticipants');
        const upcomingMeetingsEl = document.getElementById('metricUpcomingMeetings');
        const pendingResponsesEl = document.getElementById('metricPendingResponses');

        const now = new Date();

        // Total meetings
        if (totalMeetingsEl) {
            totalMeetingsEl.textContent = String(allBookings.length);
        }

        // Total attendees across meetings
        if (totalParticipantsEl) {
            totalParticipantsEl.textContent = String(allParticipants.length);
        }

        // Upcoming meetings (start_time in future or today)
        if (upcomingMeetingsEl) {
            const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const upcomingCount = allBookings.filter((b) => {
                if (!b.end_time) return false;
                const endTime = new Date(String(b.end_time).replace(' ', 'T'));
                return endTime >= todayStart;
            }).length;
            upcomingMeetingsEl.textContent = String(upcomingCount);
        }

        // Pending attendee responses
        if (pendingResponsesEl) {
            const pendingCount = allParticipants.filter((p) => {
                const st = String(p.response_status || '').toLowerCase();
                return st === 'pending';
            }).length;
            pendingResponsesEl.textContent = String(pendingCount);
        }
    }

    /* ==========================================================================
       FILTER CONTROLS
       ========================================================================== */

    function setupParticipantFilters() {
        const searchInput = document.getElementById('participantSearch');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const typeFilter = document.getElementById('typeFilter');
        const bookingFilter = document.getElementById('bookingFilter');

        searchInput?.addEventListener('input', applyParticipantFilters);
        statusFilter?.addEventListener('change', applyParticipantFilters);
        dateFilter?.addEventListener('change', applyParticipantFilters);
        typeFilter?.addEventListener('change', applyParticipantFilters);
        bookingFilter?.addEventListener('change', applyParticipantFilters);
    }

    function applyParticipantFilters() {
        const searchInput = document.getElementById('participantSearch');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const typeFilter = document.getElementById('typeFilter');
        const bookingFilter = document.getElementById('bookingFilter');

        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const statusVal = statusFilter ? statusFilter.value.trim().toLowerCase() : '';
        const dateVal = dateFilter ? dateFilter.value.trim().toLowerCase() : '';
        const typeVal = typeFilter ? typeFilter.value.trim().toLowerCase() : '';
        const bookingVal = bookingFilter ? bookingFilter.value.trim() : '';

        const now = new Date();
        const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const todayEnd = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);

        const filtered = allBookings.filter((b) => {
            // 1. Booking Filter
            if (bookingVal && String(b.id) !== bookingVal) {
                return false;
            }

            // 2. Status Filter
            if (statusVal && String(b.status || '').toLowerCase() !== statusVal) {
                return false;
            }

            // 3. Date Filter
            if (dateVal && b.start_time) {
                const startDate = new Date(String(b.start_time).replace(' ', 'T'));
                const endDate = b.end_time ? new Date(String(b.end_time).replace(' ', 'T')) : startDate;

                if (dateVal === 'today') {
                    if (startDate < todayStart || startDate > todayEnd) {
                        return false;
                    }
                } else if (dateVal === 'upcoming') {
                    if (endDate < now) {
                        return false;
                    }
                } else if (dateVal === 'past') {
                    if (endDate >= now) {
                        return false;
                    }
                }
            }

            // 4. Role / Type Filter
            if (typeVal) {
                const bookingParts = allParticipants.filter((p) => String(p.booking_id) === String(b.id));
                if (typeVal === 'organizer') {
                    const hasOrg = Boolean(b.user_id) || bookingParts.some((p) => String(p.participant_type).toLowerCase() === 'organizer');
                    if (!hasOrg) return false;
                } else if (typeVal === 'participant') {
                    const hasPart = bookingParts.some((p) => String(p.participant_type).toLowerCase() === 'participant');
                    if (!hasPart) return false;
                } else if (typeVal === 'guest') {
                    const hasGuest = bookingParts.some((p) => String(p.participant_type).toLowerCase() === 'guest');
                    if (!hasGuest) return false;
                }
            }

            // 5. Text Search (Matches meeting title, room, organizer, or any attendee)
            if (query) {
                const orgUser = usersMap.get(String(b.user_id));
                const orgName = orgUser ? `${orgUser.first_name || ''} ${orgUser.last_name || ''}`.trim() : (b.organizer_name || '');
                const orgEmail = orgUser?.email || b.organizer_email || '';
                const orgDept = departmentsMap.get(String(orgUser?.department_id))?.name || b.organizer_department_name || '';

                const bookingParts = allParticipants.filter((p) => String(p.booking_id) === String(b.id));
                const attendeesText = bookingParts.map((p) => {
                    const u = usersMap.get(String(p.user_id));
                    const name = u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : '';
                    const email = u?.email || '';
                    const dept = departmentsMap.get(String(u?.department_id))?.name || '';
                    const role = rolesMap.get(String(u?.role_id))?.name || '';
                    return `${name} ${email} ${dept} ${role}`;
                }).join(' ');

                const searchable = [
                    b.title || '',
                    b.description || '',
                    b.room_name || '',
                    b.room_code || '',
                    b.status || '',
                    orgName,
                    orgEmail,
                    orgDept,
                    attendeesText
                ].join(' ').toLowerCase();

                if (!searchable.includes(query)) {
                    return false;
                }
            }

            return true;
        });

        renderMeetingCards(filtered);
    }

    /* ==========================================================================
       RENDERING: MEETING-CENTRIC CARDS
       ========================================================================== */

    function renderMeetingCards(bookings) {
        const container = document.getElementById('participantsCardsContainer');
        const empty = document.getElementById('participantsEmpty');

        if (!container) return;

        container.innerHTML = '';

        if (!bookings.length) {
            container.classList.add('d-none');
            empty?.classList.remove('d-none');
            return;
        }

        empty?.classList.add('d-none');
        container.classList.remove('d-none');

        const now = new Date();
        const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const todayEnd = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);

        bookings.forEach((b) => {
            const card = document.createElement('div');
            card.className = 'meeting-participant-card glass-panel animate-fade-up';
            card.dataset.bookingId = String(b.id);

            // Determine if meeting is today
            let isToday = false;
            if (b.start_time) {
                const sDate = new Date(String(b.start_time).replace(' ', 'T'));
                if (sDate >= todayStart && sDate <= todayEnd) {
                    isToday = true;
                }
            }

            // Meeting Date & Time
            const dateStr = formatMeetingDate(b.start_time);
            const timeStr = formatMeetingTime(b.start_time, b.end_time);

            // Meeting Status
            const rawStatus = String(b.status || 'pending').toLowerCase();
            const statusLabel = rawStatus.charAt(0).toUpperCase() + rawStatus.slice(1);
            let statusIcon = 'bi-clock';
            if (rawStatus === 'approved') statusIcon = 'bi-check-circle';
            else if (rawStatus === 'rejected') statusIcon = 'bi-x-circle';
            else if (rawStatus === 'cancelled') statusIcon = 'bi-slash-circle';

            // Participants for this booking
            const bookingParts = allParticipants.filter((p) => String(p.booking_id) === String(b.id));

            // Determine Organizer (AUTHORITATIVE: booking.user_id)
            const organizerUserId = b.user_id ? String(b.user_id) : (
                bookingParts.find((p) => String(p.participant_type).toLowerCase() === 'organizer')?.user_id
            );

            const organizerUser = organizerUserId ? usersMap.get(String(organizerUserId)) : null;
            const orgName = organizerUser
                ? `${organizerUser.first_name || ''} ${organizerUser.last_name || ''}`.trim() || organizerUser.email
                : (b.organizer_name || 'Meeting Organizer');
            const orgEmail = organizerUser?.email || b.organizer_email || '—';
            const orgInitials = getInitials(orgName);

            const orgDeptName = departmentsMap.get(String(organizerUser?.department_id))?.name || b.organizer_department_name || '';
            const orgRoleName = rolesMap.get(String(organizerUser?.role_id))?.name || 'Employee';
            const orgDeptRole = [orgDeptName, orgRoleName].filter(Boolean).join(' · ') || 'Organizer';

            // Check if organizer has an entry in booking_participants
            const orgParticipantRecord = bookingParts.find((p) => String(p.user_id) === String(organizerUserId));
            const orgStatus = orgParticipantRecord ? String(orgParticipantRecord.response_status || 'accepted').toLowerCase() : 'accepted';
            const orgStatusLabel = orgStatus.charAt(0).toUpperCase() + orgStatus.slice(1);

            // Remaining Participants (STRICTLY EXCLUDES ORGANIZER - ZERO DUPLICATION)
            const otherParticipants = bookingParts.filter((p) => String(p.user_id) !== String(organizerUserId));

            // Total attendee count (Organizer + other participants)
            const totalAttendeeCount = (organizerUserId ? 1 : 0) + otherParticipants.length;
            const attendeePillText = totalAttendeeCount === 1 ? '1 Attendee' : `${totalAttendeeCount} Attendees`;

            // Build HTML
            let cardHtml = `
                <div class="meeting-card-header">
                    <div class="meeting-card-main-info">
                        <div class="meeting-badge-row">
                            <span class="booking-status booking-status-${escapeHtml(rawStatus)}">
                                <i class="bi ${statusIcon}"></i> ${escapeHtml(statusLabel)}
                            </span>
                            <span class="meeting-count-pill">
                                <i class="bi bi-people-fill"></i> ${escapeHtml(attendeePillText)}
                            </span>
                            ${isToday ? '<span class="meeting-today-pill"><i class="bi bi-sun"></i> Today</span>' : ''}
                        </div>
                        <h3 class="meeting-card-title">${escapeHtml(b.title || `Meeting #${b.id}`)}</h3>
                        <div class="meeting-card-meta">
                            <span class="meta-item"><i class="bi bi-calendar3"></i> ${escapeHtml(dateStr)}</span>
                            <span class="meta-dot">·</span>
                            <span class="meta-item"><i class="bi bi-clock"></i> ${escapeHtml(timeStr)}</span>
                            <span class="meta-dot">·</span>
                            <span class="meta-item"><i class="bi bi-geo-alt"></i> ${escapeHtml(b.room_name || 'Main Room')}</span>
                        </div>
                    </div>
                    <div class="meeting-card-actions">
                        <button
                            type="button"
                            class="btn-card-action add-attendee-btn"
                            data-booking-id="${escapeHtml(b.id)}"
                            title="Add attendee to ${escapeHtml(b.title)}"
                            aria-label="Add attendee to ${escapeHtml(b.title)}"
                        >
                            <i class="bi bi-person-plus"></i> Add Attendee
                        </button>
                    </div>
                </div>

                <!-- 1. ORGANIZER SECTION (ALWAYS FIRST) -->
                <div class="meeting-section-group organizer-section">
                    <div class="meeting-section-label">
                        <i class="bi bi-star-fill"></i> ORGANIZER
                    </div>
                    <div class="attendee-row organizer-row">
                        <div class="attendee-avatar organizer-avatar" title="${escapeHtml(orgName)}">
                            ${escapeHtml(orgInitials)}
                        </div>
                        <div class="attendee-info">
                            <div class="attendee-name-row">
                                <span class="attendee-name">${escapeHtml(orgName)}</span>
                                <span class="attendee-tag tag-organizer">Organizer</span>
                            </div>
                            <div class="attendee-meta">
                                <span>${escapeHtml(orgDeptRole)}</span>
                                <span class="meta-dot">·</span>
                                <span class="attendee-email">${escapeHtml(orgEmail)}</span>
                            </div>
                        </div>
                        <div class="attendee-status-wrap">
                            <span class="response-status-badge status-${escapeHtml(orgStatus)}">
                                <i class="bi bi-check-circle-fill"></i> ${escapeHtml(orgStatusLabel)}
                            </span>
                        </div>
                        ${orgParticipantRecord ? `
                        <div class="attendee-actions">
                            <button
                                type="button"
                                class="participant-action-btn participant-edit-btn"
                                data-booking-id="${escapeHtml(b.id)}"
                                data-user-id="${escapeHtml(organizerUserId)}"
                                title="Edit organizer status"
                                aria-label="Edit organizer status"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>` : ''}
                    </div>
                </div>

                <!-- 2. PARTICIPANTS SECTION (FOLLOWING ORGANIZER) -->
                <div class="meeting-section-group participants-section">
                    <div class="meeting-section-label">
                        <i class="bi bi-people"></i> PARTICIPANTS (${otherParticipants.length})
                    </div>
                    <div class="attendee-list">
            `;

            if (otherParticipants.length === 0) {
                cardHtml += `
                    <div class="attendee-empty-state">
                        <span>No additional participants assigned to this meeting.</span>
                    </div>
                `;
            } else {
                otherParticipants.forEach((p) => {
                    const u = usersMap.get(String(p.user_id));
                    const userName = u
                        ? `${u.first_name || ''} ${u.last_name || ''}`.trim() || u.email || `User #${p.user_id}`
                        : `User #${p.user_id}`;
                    const userEmail = u?.email || '—';
                    const userInitials = getInitials(userName);

                    const userDept = departmentsMap.get(String(u?.department_id))?.name || '';
                    const userRole = rolesMap.get(String(u?.role_id))?.name || 'Employee';
                    const userDeptRole = [userDept, userRole].filter(Boolean).join(' · ') || 'Participant';

                    const pType = String(p.participant_type || 'participant').toLowerCase();
                    const pStatus = String(p.response_status || 'pending').toLowerCase();
                    const pStatusLabel = pStatus.charAt(0).toUpperCase() + pStatus.slice(1);

                    let statusIconClass = 'bi-clock';
                    if (pStatus === 'accepted') statusIconClass = 'bi-check-circle-fill';
                    else if (pStatus === 'declined') statusIconClass = 'bi-x-circle-fill';
                    else if (pStatus === 'tentative') statusIconClass = 'bi-question-circle-fill';

                    cardHtml += `
                        <div class="attendee-row">
                            <div class="attendee-avatar" title="${escapeHtml(userName)}">
                                ${escapeHtml(userInitials)}
                            </div>
                            <div class="attendee-info">
                                <div class="attendee-name-row">
                                    <span class="attendee-name">${escapeHtml(userName)}</span>
                                    ${pType === 'guest' ? '<span class="attendee-tag tag-guest">Guest</span>' : ''}
                                </div>
                                <div class="attendee-meta">
                                    <span>${escapeHtml(userDeptRole)}</span>
                                    <span class="meta-dot">·</span>
                                    <span class="attendee-email">${escapeHtml(userEmail)}</span>
                                </div>
                            </div>
                            <div class="attendee-status-wrap">
                                <span class="response-status-badge status-${escapeHtml(pStatus)}">
                                    <i class="bi ${statusIconClass}"></i> ${escapeHtml(pStatusLabel)}
                                </span>
                            </div>
                            <div class="attendee-actions">
                                <button
                                    type="button"
                                    class="participant-action-btn participant-edit-btn"
                                    data-booking-id="${escapeHtml(b.id)}"
                                    data-user-id="${escapeHtml(p.user_id)}"
                                    title="Edit participant"
                                    aria-label="Edit participant"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button
                                    type="button"
                                    class="participant-action-btn participant-delete-btn"
                                    data-booking-id="${escapeHtml(b.id)}"
                                    data-user-id="${escapeHtml(p.user_id)}"
                                    title="Remove participant"
                                    aria-label="Remove participant"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            }

            cardHtml += `
                    </div>
                </div>
            `;

            card.innerHTML = cardHtml;
            container.appendChild(card);
        });

        setupParticipantActionButtons();
    }

    /* ==========================================================================
       ACTION BUTTONS & MODAL OPENERS
       ========================================================================== */

    function setupParticipantActionButtons() {
        // 1. Add Attendee button on specific meeting card
        document.querySelectorAll('.add-attendee-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const bookingId = btn.dataset.bookingId;
                resetParticipantForm();
                const bookingSelect = document.getElementById('participantBooking');
                if (bookingSelect && bookingId) {
                    bookingSelect.value = String(bookingId);
                    if (isSelect2Available()) {
                        const b = bookingsMap.get(String(bookingId));
                        setSelect2Option('#participantBooking', bookingId, b?.title || `Meeting #${bookingId}`);
                    }
                }
                openParticipantModal();
            });
        });

        // 2. Edit button on participant rows
        document.querySelectorAll('.participant-edit-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const bookingId = btn.dataset.bookingId;
                const userId = btn.dataset.userId;
                editParticipant(bookingId, userId);
            });
        });

        // 3. Delete button on participant rows
        document.querySelectorAll('.participant-delete-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const bookingId = btn.dataset.bookingId;
                const userId = btn.dataset.userId;
                deleteParticipant(bookingId, userId);
            });
        });
    }

    /* ==========================================================================
       DROPDOWNS
       ========================================================================== */

    function populateFilterDropdowns() {
        const bookingFilter = document.getElementById('bookingFilter');
        if (!bookingFilter) return;

        const currentSelected = bookingFilter.value;
        bookingFilter.innerHTML = '<option value="">All Meetings</option>';

        allBookings.forEach((b) => {
            const option = document.createElement('option');
            option.value = String(b.id);

            let label = b.title || `Meeting #${b.id}`;
            if (b.start_time) {
                const dateStr = formatMeetingDate(b.start_time);
                if (dateStr !== '—') {
                    label += ` (${dateStr})`;
                }
            }
            option.textContent = label;
            bookingFilter.appendChild(option);
        });

        if (currentSelected) {
            bookingFilter.value = currentSelected;
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

    function setupParticipantSelect2() {
        if (!isSelect2Available()) return;

        const $modal = $('#participantModal');

        if (!$('#participantBooking').hasClass('select2-hidden-accessible')) {
            $('#participantBooking').select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: 'Search and select meeting...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '/api/bookings/select2',
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

        if (!$('#participantUser').hasClass('select2-hidden-accessible')) {
            $('#participantUser').select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: 'Search attendee by name or email...',
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

    function populateModalDropdowns() {
        const bookingSelect = document.getElementById('participantBooking');
        const userSelect = document.getElementById('participantUser');

        if (bookingSelect) {
            bookingSelect.innerHTML = '<option value="">Select a meeting...</option>';
            allBookings.forEach((b) => {
                const option = document.createElement('option');
                option.value = String(b.id);
                let label = b.title || `Meeting #${b.id}`;
                if (b.start_time) {
                    const dateStr = formatMeetingDate(b.start_time);
                    if (dateStr !== '—') {
                        label += ` — ${dateStr}`;
                    }
                }
                option.textContent = label;
                bookingSelect.appendChild(option);
            });
        }

        if (userSelect) {
            userSelect.innerHTML = '<option value="">Select a user...</option>';
            allUsers.forEach((u) => {
                const option = document.createElement('option');
                option.value = String(u.id);
                const fullName = `${u.first_name || ''} ${u.last_name || ''}`.trim() || `User #${u.id}`;
                const dept = departmentsMap.get(String(u.department_id))?.name;
                option.textContent = dept ? `${fullName} (${dept})` : (u.email ? `${fullName} (${u.email})` : fullName);
                userSelect.appendChild(option);
            });
        }
    }

    /* ==========================================================================
       MODAL HANDLING
       ========================================================================== */

    function setupParticipantModal() {
        const modal = document.getElementById('participantModal');
        const newBtn = document.getElementById('newParticipantBtn');
        const closeBtn = document.getElementById('closeParticipantModal');
        const cancelBtn = document.getElementById('cancelParticipantBtn');
        const form = document.getElementById('participantForm');

        if (!modal || !newBtn || !form) return;

        newBtn.addEventListener('click', () => {
            resetParticipantForm();
            openParticipantModal();
        });

        closeBtn?.addEventListener('click', closeParticipantModalWindow);
        cancelBtn?.addEventListener('click', closeParticipantModalWindow);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeParticipantModalWindow();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
                closeParticipantModalWindow();
            }
        });

        form.addEventListener('submit', handleParticipantFormSubmit);
        setupParticipantSelect2();
    }

    function openParticipantModal() {
        const modal = document.getElementById('participantModal');
        if (!modal) return;

        modal.classList.remove('d-none');
        document.body.classList.add('booking-modal-open');

        if (isSelect2Available()) {
            $('#participantBooking, #participantUser').trigger('change.select2');
        }

        setTimeout(() => {
            const firstInput = document.getElementById('participantBooking');
            if (firstInput && !firstInput.disabled && !firstInput.value) {
                firstInput.focus();
            } else {
                document.getElementById('participantUser')?.focus();
            }
        }, 50);
    }

    function closeParticipantModalWindow() {
        const modal = document.getElementById('participantModal');
        if (!modal) return;

        modal.classList.add('d-none');
        document.body.classList.remove('booking-modal-open');
    }

    function resetParticipantForm() {
        const form = document.getElementById('participantForm');
        form?.reset();

        const hiddenBookingId = document.getElementById('participantBookingId');
        const hiddenUserId = document.getElementById('participantUserId');
        const bookingSelect = document.getElementById('participantBooking');
        const userSelect = document.getElementById('participantUser');
        const typeSelect = document.getElementById('participantType');
        const statusSelect = document.getElementById('participantStatus');

        if (hiddenBookingId) hiddenBookingId.value = '';
        if (hiddenUserId) hiddenUserId.value = '';
        if (bookingSelect) bookingSelect.disabled = false;
        if (userSelect) userSelect.disabled = false;
        if (typeSelect) typeSelect.value = 'participant';
        if (statusSelect) statusSelect.value = 'pending';

        if (isSelect2Available()) {
            $('#participantBooking').prop('disabled', false);
            $('#participantUser').prop('disabled', false);
            setSelect2Option('#participantBooking', null);
            setSelect2Option('#participantUser', null);
        }

        const modalTitle = document.getElementById('participantModalTitle');
        if (modalTitle) modalTitle.textContent = 'Add Participant';

        const modalSubtitle = document.getElementById('participantModalSubtitle');
        if (modalSubtitle) modalSubtitle.textContent = 'Assign an attendee to a meeting booking.';

        const submitBtn = document.getElementById('submitParticipantBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Add Participant';
        }

        hideParticipantFormMessages();
    }

    /* ==========================================================================
       EDIT PARTICIPANT
       ========================================================================== */

    function editParticipant(bookingId, userId) {
        const participant = findParticipant(bookingId, userId);
        if (!participant) {
            showAppNotification('Unable to find the selected participant record.', 'error', 'Participant Not Found');
            return;
        }

        const hiddenBookingId = document.getElementById('participantBookingId');
        const hiddenUserId = document.getElementById('participantUserId');
        const bookingSelect = document.getElementById('participantBooking');
        const userSelect = document.getElementById('participantUser');
        const typeSelect = document.getElementById('participantType');
        const statusSelect = document.getElementById('participantStatus');

        if (hiddenBookingId) hiddenBookingId.value = String(bookingId);
        if (hiddenUserId) hiddenUserId.value = String(userId);

        if (bookingSelect) {
            bookingSelect.value = String(bookingId);
            bookingSelect.disabled = true;
            if (isSelect2Available()) {
                const b = bookingsMap.get(String(bookingId));
                setSelect2Option('#participantBooking', bookingId, b?.title || `Booking #${bookingId}`);
                $('#participantBooking').prop('disabled', true);
            }
        }

        if (userSelect) {
            userSelect.value = String(userId);
            userSelect.disabled = true;
            if (isSelect2Available()) {
                const u = usersMap.get(String(userId));
                const uName = u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : `User #${userId}`;
                setSelect2Option('#participantUser', userId, uName);
                $('#participantUser').prop('disabled', true);
            }
        }

        if (typeSelect) {
            typeSelect.value = participant.participant_type || 'participant';
        }

        if (statusSelect) {
            statusSelect.value = participant.response_status || 'pending';
        }

        const modalTitle = document.getElementById('participantModalTitle');
        if (modalTitle) modalTitle.textContent = 'Edit Participant';

        const modalSubtitle = document.getElementById('participantModalSubtitle');
        if (modalSubtitle) modalSubtitle.textContent = 'Update participant role and response status.';

        const submitBtn = document.getElementById('submitParticipantBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save Changes';
        }

        hideParticipantFormMessages();
        openParticipantModal();
    }

    /* ==========================================================================
       FORM SUBMISSION (CREATE / UPDATE)
       ========================================================================== */

    async function handleParticipantFormSubmit(event) {
        event.preventDefault();

        const form = document.getElementById('participantForm');
        const submitButton = document.getElementById('submitParticipantBtn');
        if (!form || !submitButton) return;

        hideParticipantFormMessages();

        const hiddenBookingId = document.getElementById('participantBookingId').value.trim();
        const hiddenUserId = document.getElementById('participantUserId').value.trim();
        const bookingSelect = document.getElementById('participantBooking');
        const userSelect = document.getElementById('participantUser');
        const typeSelect = document.getElementById('participantType');
        const statusSelect = document.getElementById('participantStatus');

        const isEditing = Boolean(hiddenBookingId && hiddenUserId);
        const bookingId = isEditing ? hiddenBookingId : bookingSelect.value.trim();
        const userId = isEditing ? hiddenUserId : userSelect.value.trim();
        const participantType = typeSelect.value.trim();
        const responseStatus = statusSelect.value.trim();

        if (!bookingId) {
            showParticipantFormError('Please select a meeting.');
            return;
        }
        if (!userId) {
            showParticipantFormError('Please select an attendee.');
            return;
        }
        if (!participantType) {
            showParticipantFormError('Please select a participant type.');
            return;
        }
        if (!responseStatus) {
            showParticipantFormError('Please select a response status.');
            return;
        }

        const payload = isEditing
            ? { participant_type: participantType, response_status: responseStatus }
            : {
                  booking_id: Number(bookingId),
                  user_id: Number(userId),
                  participant_type: participantType,
                  response_status: responseStatus
              };

        const url = isEditing
            ? `/booking-participants/${bookingId}/${userId}`
            : '/booking-participants';
        const method = isEditing ? 'PUT' : 'POST';

        try {
            submitButton.disabled = true;
            submitButton.innerHTML = `<i class="bi bi-arrow-repeat spin"></i> ${isEditing ? 'Saving...' : 'Adding...'}`;

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
                handleParticipantApiError(response.status, result);
                return;
            }

            const successMessage = result.message || (isEditing ? 'Participant updated successfully.' : 'Participant added successfully.');
            closeParticipantModalWindow();
            showAppNotification(successMessage, 'success', isEditing ? 'Participant Updated' : 'Participant Added');
            await loadParticipantsData();

        } catch (error) {
            console.error('Unable to save participant:', error);
            showParticipantFormError('Unable to save participant. Please try again.');
            showAppNotification('Unable to save participant. Please try again.', 'error', 'Participant Save Failed');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="bi bi-check-lg"></i> ${isEditing ? 'Save Changes' : 'Add Participant'}`;
        }
    }

    /* ==========================================================================
       DELETE PARTICIPANT
       ========================================================================== */

    async function deleteParticipant(bookingId, userId) {
        const participant = findParticipant(bookingId, userId);
        if (!participant) {
            showAppNotification('Unable to find the selected participant.', 'error', 'Participant Not Found');
            return;
        }

        const user = usersMap.get(String(userId));
        const booking = bookingsMap.get(String(bookingId));

        const attendeeName = user
            ? `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.email || `User #${userId}`
            : `User #${userId}`;
        const bookingTitle = booking?.title || `Booking #${bookingId}`;

        showAppConfirm(
            `Are you sure you want to remove "${attendeeName}" from "${bookingTitle}"?`,
            async () => {
                await performParticipantDelete(bookingId, userId, attendeeName);
            },
            'Remove Participant',
            'Remove Participant'
        );
    }

    async function performParticipantDelete(bookingId, userId, attendeeName) {
        try {
            const response = await fetch(`/booking-participants/${bookingId}/${userId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Unable to remove participant.');
            }

            await loadParticipantsData();
            showAppNotification(result.message || `"${attendeeName}" has been removed from the meeting.`, 'success', 'Participant Removed');

        } catch (error) {
            console.error('Unable to delete participant:', error);
            showAppNotification(error.message || 'Unable to remove participant. Please try again.', 'error', 'Remove Failed');
        }
    }

    /* ==========================================================================
       HELPERS & UTILITIES
       ========================================================================== */

    function findParticipant(bookingId, userId) {
        return allParticipants.find(
            (p) => String(p.booking_id) === String(bookingId) && String(p.user_id) === String(userId)
        );
    }

    function formatMeetingDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(String(dateStr).replace(' ', 'T'));
        if (isNaN(d.getTime())) return dateStr;

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const target = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const diffDays = Math.round((target - today) / (1000 * 60 * 60 * 24));

        const dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
        const monthName = d.toLocaleDateString('en-US', { month: 'short' });
        const dayNum = d.getDate();

        if (diffDays === 0) {
            return 'Today';
        } else if (diffDays === 1) {
            return 'Tomorrow';
        } else if (diffDays === -1) {
            return 'Yesterday';
        } else {
            return `${dayName}, ${monthName} ${dayNum}`;
        }
    }

    function formatMeetingTime(startStr, endStr) {
        if (!startStr) return '—';
        const s = new Date(String(startStr).replace(' ', 'T'));
        const startTime = s.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
        if (!endStr) return startTime;
        const e = new Date(String(endStr).replace(' ', 'T'));
        const endTime = e.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
        return `${startTime} – ${endTime}`;
    }

    function getInitials(name) {
        if (!name) return '??';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) {
            return parts[0].substring(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showParticipantFormError(message) {
        const errorAlert = document.getElementById('participantFormError');
        const errorText = document.getElementById('participantFormErrorText');
        if (!errorAlert || !errorText) return;

        errorText.textContent = message;
        errorAlert.classList.remove('d-none');
    }

    function hideParticipantFormMessages() {
        const errorAlert = document.getElementById('participantFormError');
        const successAlert = document.getElementById('participantFormSuccess');
        errorAlert?.classList.add('d-none');
        successAlert?.classList.add('d-none');
    }

    function handleParticipantApiError(status, result) {
        if (status === 422 && result.errors) {
            const firstError = Object.values(result.errors)[0];
            showParticipantFormError(firstError || 'Validation failed. Please review your inputs.');
            return;
        }
        showParticipantFormError(result.message || 'An error occurred while saving the participant.');
    }

    function showAppNotification(message, type = 'info', title = '') {
        if (typeof window.showAppNotification === 'function') {
            window.showAppNotification(message, type, title);
            return;
        }

        const existing = document.querySelector('.app-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `app-notification app-notification-${type} animate-fade-in`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');

        let icon = 'bi-info-circle';
        if (type === 'success') icon = 'bi-check-circle';
        else if (type === 'error') icon = 'bi-exclamation-triangle';
        else if (type === 'warning') icon = 'bi-exclamation-circle';

        toast.innerHTML = `
            <i class="bi ${icon}"></i>
            <div class="app-notification-content">
                ${title ? `<div class="app-notification-title">${escapeHtml(title)}</div>` : ''}
                <div class="app-notification-message">${escapeHtml(message)}</div>
            </div>
            <button type="button" class="app-notification-close" aria-label="Close notification">
                <i class="bi bi-x"></i>
            </button>
        `;

        document.body.appendChild(toast);

        toast.querySelector('.app-notification-close')?.addEventListener('click', () => {
            toast.remove();
        });

        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function showAppConfirm(message, onConfirm, title = 'Confirm Action', confirmBtnText = 'Confirm') {
        if (typeof window.showAppConfirm === 'function') {
            window.showAppConfirm(message, onConfirm, title, confirmBtnText);
            return;
        }

        const existing = document.getElementById('appConfirmModal');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'appConfirmModal';
        overlay.className = 'booking-modal-overlay';
        overlay.innerHTML = `
            <div class="booking-modal booking-modal-sm" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                <div class="booking-modal-header">
                    <div>
                        <h2 id="confirmTitle">${escapeHtml(title)}</h2>
                    </div>
                    <button type="button" class="booking-modal-close" id="closeConfirmModal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="booking-modal-body" style="padding: 16px 24px;">
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px;">${escapeHtml(message)}</p>
                </div>
                <div class="booking-modal-footer">
                    <button type="button" class="btn-booking-cancel" id="cancelConfirmBtn">Cancel</button>
                    <button type="button" class="btn-danger-action" id="acceptConfirmBtn">${escapeHtml(confirmBtnText)}</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        const close = () => overlay.remove();
        overlay.querySelector('#closeConfirmModal')?.addEventListener('click', close);
        overlay.querySelector('#cancelConfirmBtn')?.addEventListener('click', close);
        overlay.querySelector('#acceptConfirmBtn')?.addEventListener('click', async () => {
            close();
            if (typeof onConfirm === 'function') {
                await onConfirm();
            }
        });
    }

})();
