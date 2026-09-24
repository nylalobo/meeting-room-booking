/**
 * MeetSpace Enterprise Suite - Dashboard Module JavaScript
 *
 * Handles:
 * - Loading and rendering dynamic dashboard statistics (total rooms, bookings, pending, users)
 * - Animated count-up numbers with ease-out timing
 * - Dynamic time-of-day greeting context
 * - Interactive 3D flip operational flash-cards
 * - Dynamic Upcoming Meetings & Quick Room Availability with pulsing indicators
 */

document.addEventListener('DOMContentLoaded', () => {

    if (document.getElementById('total-rooms')) {
        updateTimeGreeting();
        initializeFlashCards();
        loadDashboardStats();
    }
});


/* ==========================================================================
   TIME-OF-DAY GREETING
   ========================================================================== */

function updateTimeGreeting() {
    const greetingText = document.getElementById('heroGreetingText');
    const subtitle = document.getElementById('welcomeTimeSubtitle');
    const now = new Date();
    const hours = now.getHours();

    let timeGreeting = 'Good morning';
    if (hours >= 12 && hours < 17) {
        timeGreeting = 'Good afternoon';
    } else if (hours >= 17 || hours < 5) {
        timeGreeting = 'Good evening';
    }

    if (greetingText) {
        greetingText.textContent = timeGreeting;
    }
    if (subtitle) {
        subtitle.textContent = `${timeGreeting} · Here's what's happening across MeetSpace today.`;
    }
}


/* ==========================================================================
   INTERACTIVE FLASH CARDS (3D FLIP & KEYBOARD ACCESSIBILITY)
   ========================================================================== */

function initializeFlashCards() {
    const cards = document.querySelectorAll('.flash-card');

    cards.forEach((card) => {
        // Toggle on click unless clicking direct action link
        card.addEventListener('click', (e) => {
            if (e.target.closest('a') || e.target.closest('.flash-card-action-btn')) {
                return;
            }
            card.classList.toggle('is-flipped');
        });

        // Keyboard accessible flip
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                if (e.target.closest('a') || e.target.closest('.flash-card-action-btn')) {
                    return;
                }
                e.preventDefault();
                card.classList.toggle('is-flipped');
            }
        });
    });
}


/* ==========================================================================
   ANIMATED NUMBER COUNTER
   ========================================================================== */

function animateValue(element, targetValue, duration = 650) {
    if (!element) return;

    const target = parseInt(targetValue, 10);
    if (isNaN(target)) {
        element.textContent = targetValue ?? '0';
        return;
    }

    // Check prefers-reduced-motion
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || duration <= 0) {
        element.textContent = String(target);
        return;
    }

    const start = 0;
    const startTime = performance.now();

    function step(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Ease-out cubic curve
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + (target - start) * easeOut);

        element.textContent = String(current);

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            element.textContent = String(target);
        }
    }

    requestAnimationFrame(step);
}


/* ==========================================================================
   DASHBOARD DATA LOADER
   ========================================================================== */

async function loadDashboardStats() {

    try {

        const response = await fetch('/dashboard/stats');

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        const result = await response.json();

        if (result.status !== 'success') {
            throw new Error('Dashboard statistics request failed.');
        }

        const stats = result.data;

        // Render animated statistics counters
        const totalRoomsEl = document.getElementById('total-rooms');
        if (totalRoomsEl) animateValue(totalRoomsEl, stats.total_rooms ?? 0);

        const totalBookingsEl = document.getElementById('total-bookings');
        if (totalBookingsEl) animateValue(totalBookingsEl, stats.total_bookings ?? 0);

        const pendingRequestsEl = document.getElementById('pending-requests');
        if (pendingRequestsEl) animateValue(pendingRequestsEl, stats.pending_requests ?? 0);

        const activeUsersEl = document.getElementById('active-users');
        if (activeUsersEl) animateValue(activeUsersEl, stats.active_users ?? 0);

        // Update Operational Flash Cards with Real Data
        updateFlashCardsData(stats);

        // Render Dynamic Upcoming Meetings
        const upcomingBody = document.getElementById('upcomingMeetingsBody');
        if (upcomingBody && Array.isArray(stats.upcoming_meetings)) {
            if (stats.upcoming_meetings.length === 0) {
                upcomingBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted-blue" id="upcomingMeetingsEmpty">
                            <i class="bi bi-calendar-x d-block mb-1" style="font-size: 20px; opacity: 0.6;"></i>
                            <span>No upcoming meetings scheduled</span>
                        </td>
                    </tr>
                `;
            } else {
                upcomingBody.innerHTML = stats.upcoming_meetings.map((m) => {
                    const title = escapeHtmlSafe(m.title || 'Untitled Meeting');
                    const roomName = escapeHtmlSafe(m.room_name || 'Room');
                    const date = escapeHtmlSafe(m.date || '');
                    const timeRange = escapeHtmlSafe(m.time_range || '');
                    const statusClass = escapeHtmlSafe(m.status_class || 'badge-upcoming');
                    const statusBadge = escapeHtmlSafe(m.status_badge || 'Upcoming');

                    return `
                        <tr class="hover-lift">
                            <td class="fw-semibold text-white">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="pulsing-dot pulsing-dot-green"></span>
                                    <span>${title}</span>
                                </div>
                            </td>
                            <td class="text-muted-blue">${roomName}</td>
                            <td class="text-muted-blue">
                                <div class="fw-medium text-white">${date}</div>
                                <div style="font-size: 11.5px; opacity: 0.85;">${timeRange}</div>
                            </td>
                            <td class="text-end">
                                <span class="badge-status ${statusClass}">${statusBadge}</span>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // Render Dynamic Quick Availability with Pulsing Indicators
        const quickRoomsList = document.getElementById('quickAvailabilityList');
        if (quickRoomsList && Array.isArray(stats.quick_rooms)) {
            if (stats.quick_rooms.length === 0) {
                quickRoomsList.innerHTML = '<div class="text-muted-blue small py-2 text-center">No active rooms found.</div>';
            } else {
                quickRoomsList.innerHTML = stats.quick_rooms.map((room) => {
                    const name = escapeHtmlSafe(room.name || 'Room');
                    const isAvailable = room.is_available === true;
                    const statusClass = escapeHtmlSafe(room.status_class || (isAvailable ? 'avail-now' : 'avail-busy'));
                    const statusText = escapeHtmlSafe(room.status_text || (isAvailable ? 'Available Now' : 'In Use'));
                    const capacity = escapeHtmlSafe(String(room.capacity || 0));
                    const floorHtml = room.floor
                        ? `<span><i class="bi bi-geo-alt"></i> ${escapeHtmlSafe(room.floor)}</span>`
                        : '';
                    const dotClass = isAvailable ? 'pulsing-dot-green' : 'pulsing-dot-amber';

                    return `
                        <div class="room-avail-item glass-card hover-lift">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="room-name d-flex align-items-center gap-2">
                                    <span class="pulsing-dot ${dotClass}"></span>
                                    <span>${name}</span>
                                </span>
                                <span class="avail-badge ${statusClass}">${statusText}</span>
                            </div>
                            <div class="room-features">
                                <span><i class="bi bi-person-fill"></i> ${capacity} Seats</span>
                                ${floorHtml}
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

    } catch (error) {

        const upcomingBody = document.getElementById('upcomingMeetingsBody');
        if (upcomingBody) {
            upcomingBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted-blue">
                        <i class="bi bi-exclamation-triangle d-block mb-1 text-danger" style="font-size: 18px;"></i>
                        <span>Unable to load upcoming meetings.</span>
                    </td>
                </tr>
            `;
        }

        const quickRoomsList = document.getElementById('quickAvailabilityList');
        if (quickRoomsList) {
            quickRoomsList.innerHTML = '<div class="text-muted-blue small py-2 text-center">Unable to load room availability.</div>';
        }

    }
}


/* ==========================================================================
   POPULATE OPERATIONAL FLASH CARDS WITH LIVE DATA
   ========================================================================== */

function updateFlashCardsData(stats) {
    if (!stats) return;

    // 1. Next Meeting Card
    const meetings = stats.upcoming_meetings;
    const meetingTitle = document.getElementById('fcMeetingTitle');
    const meetingTime = document.getElementById('fcMeetingTime');
    const meetingRoom = document.getElementById('fcMeetingRoom');
    const meetingStatus = document.getElementById('fcMeetingStatus');
    const backDetails = document.getElementById('fcBackDetails');
    const backRoomInfo = document.getElementById('fcBackRoomInfo');

    if (Array.isArray(meetings) && meetings.length > 0) {
        const next = meetings[0];
        if (meetingTitle) meetingTitle.textContent = next.title || 'Scheduled Meeting';
        if (meetingTime) meetingTime.innerHTML = `<i class="bi bi-clock"></i> <span>${escapeHtmlSafe(next.date || 'Today')} (${escapeHtmlSafe(next.time_range || '')})</span>`;
        if (meetingRoom) meetingRoom.innerHTML = `<i class="bi bi-door-open"></i> <span>${escapeHtmlSafe(next.room_name || 'Conference Room')}</span>`;
        if (meetingStatus) {
            meetingStatus.textContent = next.status_badge || 'Upcoming';
            meetingStatus.className = `badge-status ${escapeHtmlSafe(next.status_class || 'badge-upcoming')}`;
        }
        if (backDetails) backDetails.textContent = `Next upcoming session: ${next.title || 'Meeting'}.`;
        if (backRoomInfo) backRoomInfo.textContent = `Location: ${next.room_name || 'Room'} (${next.time_range || ''})`;
    } else {
        if (meetingTitle) meetingTitle.textContent = 'No Upcoming Meetings';
        if (meetingTime) meetingTime.innerHTML = `<i class="bi bi-check-circle text-success"></i> <span>Schedule is clear</span>`;
        if (meetingRoom) meetingRoom.innerHTML = `<i class="bi bi-door-open"></i> <span>All rooms ready</span>`;
        if (meetingStatus) {
            meetingStatus.textContent = 'Clear';
            meetingStatus.className = 'badge-status badge-approved';
        }
        if (backDetails) backDetails.textContent = 'You have no confirmed upcoming meetings scheduled.';
        if (backRoomInfo) backRoomInfo.textContent = 'Book a new session anytime.';
    }

    // 2. Room Availability Card
    const quickRooms = stats.quick_rooms;
    const availCount = document.getElementById('fcAvailCount');
    const topRoom = document.getElementById('fcTopRoom');
    const availSeats = document.getElementById('fcAvailSeats');

    if (Array.isArray(quickRooms) && quickRooms.length > 0) {
        const freeRooms = quickRooms.filter(r => r.is_available === true);
        const freeCount = freeRooms.length;
        if (availCount) availCount.textContent = `${freeCount} of ${quickRooms.length} Ready`;
        if (topRoom) {
            const firstFree = freeRooms[0] || quickRooms[0];
            topRoom.innerHTML = `<i class="bi bi-door-open"></i> <span>Top pick: ${escapeHtmlSafe(firstFree.name || 'Room')}</span>`;
        }
        if (availSeats) {
            const totalCap = quickRooms.reduce((acc, r) => acc + (parseInt(r.capacity, 10) || 0), 0);
            availSeats.innerHTML = `<i class="bi bi-people"></i> <span>${totalCap} total seating capacity</span>`;
        }
    }

    // 3. Pending Approvals Card
    const pendingCount = parseInt(stats.pending_requests, 10) || 0;
    const pendingTitle = document.getElementById('fcPendingTitle');
    const pendingDetail = document.getElementById('fcPendingDetail');
    const pendingBadge = document.getElementById('fcPendingBadge');

    if (pendingTitle) pendingTitle.textContent = pendingCount > 0 ? `${pendingCount} Pending Requests` : 'Approval Queue Clear';
    if (pendingDetail) {
        pendingDetail.innerHTML = pendingCount > 0
            ? `<i class="bi bi-exclamation-circle text-warning"></i> <span>${pendingCount} booking(s) awaiting sign-off</span>`
            : `<i class="bi bi-check2-circle text-success"></i> <span>All requests processed</span>`;
    }
    if (pendingBadge) {
        pendingBadge.textContent = pendingCount > 0 ? `${pendingCount} Pending` : 'All Clear';
        pendingBadge.className = pendingCount > 0 ? 'badge-status badge-pending' : 'badge-status badge-approved';
    }
}


/**
 * Safe HTML escape helper with fallback.
 */
function escapeHtmlSafe(str) {
    if (typeof escapeHtml === 'function') {
        return escapeHtml(str);
    }
    if (str === null || str === undefined) {
        return '';
    }
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
