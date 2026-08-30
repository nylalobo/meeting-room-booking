/**
 * MeetSpace Enterprise Suite - Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // Search input behavior
    const searchInput = document.querySelector('.search-input');

    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                console.log('Search query:', searchInput.value.trim());
            }
        });
    }

    // Load dashboard statistics
    loadDashboardStats();

    console.log('MeetSpace Enterprise Suite initialized.');
});

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

        document.getElementById('total-rooms').textContent = stats.total_rooms;
        document.getElementById('total-bookings').textContent = stats.total_bookings;
        document.getElementById('pending-requests').textContent = stats.pending_requests;
        document.getElementById('active-users').textContent = stats.active_users;

    } catch (error) {
        console.error('Unable to load dashboard statistics:', error);
    }
}