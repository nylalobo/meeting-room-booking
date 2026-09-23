/**
 * MeetSpace Enterprise Suite - Dashboard Module JavaScript
 *
 * Handles:
 * - Loading and rendering dashboard statistics (total rooms, total bookings, pending requests, active users)
 */

document.addEventListener('DOMContentLoaded', () => {

    // Dashboard
    if (document.getElementById('total-rooms')) {
        loadDashboardStats();
    }
});


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

        // Render statistics counters
        const totalRoomsEl = document.getElementById('total-rooms');
        if (totalRoomsEl) totalRoomsEl.textContent = stats.total_rooms ?? '0';

        const totalBookingsEl = document.getElementById('total-bookings');
        if (totalBookingsEl) totalBookingsEl.textContent = stats.total_bookings ?? '0';

        const pendingRequestsEl = document.getElementById('pending-requests');
        if (pendingRequestsEl) pendingRequestsEl.textContent = stats.pending_requests ?? '0';

        const activeUsersEl = document.getElementById('active-users');
        if (activeUsersEl) activeUsersEl.textContent = stats.active_users ?? '0';

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
                        <tr>
                            <td class="fw-semibold text-white">${title}</td>
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

        // Render Dynamic Quick Availability
        const quickRoomsList = document.getElementById('quickAvailabilityList');
        if (quickRoomsList && Array.isArray(stats.quick_rooms)) {
            if (stats.quick_rooms.length === 0) {
                quickRoomsList.innerHTML = '<div class="text-muted-blue small py-2 text-center">No active rooms found.</div>';
            } else {
                quickRoomsList.innerHTML = stats.quick_rooms.map((room) => {
                    const name = escapeHtmlSafe(room.name || 'Room');
                    const statusClass = escapeHtmlSafe(room.status_class || 'avail-now');
                    const statusText = escapeHtmlSafe(room.status_text || 'Available Now');
                    const capacity = escapeHtmlSafe(String(room.capacity || 0));
                    const floorHtml = room.floor
                        ? `<span><i class="bi bi-geo-alt"></i> ${escapeHtmlSafe(room.floor)}</span>`
                        : '';

                    return `
                        <div class="room-avail-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="room-name">${name}</span>
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
