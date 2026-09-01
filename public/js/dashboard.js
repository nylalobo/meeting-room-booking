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
