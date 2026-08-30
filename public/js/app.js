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

    // Dashboard
    if (document.getElementById('total-rooms')) {
        loadDashboardStats();
    }

    // Bookings page
    if (document.getElementById('bookingsTableBody')) {
        loadBookings();
        setupBookingFilters();
    }

    console.log('MeetSpace Enterprise Suite initialized.');
});


/* ==========================================================================
   Dashboard
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

        document.getElementById('total-rooms').textContent = stats.total_rooms;
        document.getElementById('total-bookings').textContent = stats.total_bookings;
        document.getElementById('pending-requests').textContent = stats.pending_requests;
        document.getElementById('active-users').textContent = stats.active_users;

    } catch (error) {
        console.error('Unable to load dashboard statistics:', error);
    }
}


/* ==========================================================================
   Bookings
   ========================================================================== */

let allBookings = [];


async function loadBookings() {
    const loading = document.getElementById('bookingsLoading');
    const error = document.getElementById('bookingsError');
    const empty = document.getElementById('bookingsEmpty');
    const tableWrapper = document.getElementById('bookingsTableWrapper');

    try {
        loading.classList.remove('d-none');
        error.classList.add('d-none');
        empty.classList.add('d-none');
        tableWrapper.classList.add('d-none');

        const response = await fetch('/api/bookings');

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        const result = await response.json();

        if (result.status !== 'success') {
            throw new Error('Bookings request failed.');
        }

        allBookings = result.data || [];

        loading.classList.add('d-none');

        renderBookings(allBookings);

    } catch (err) {
        console.error('Unable to load bookings:', err);

        loading.classList.add('d-none');
        tableWrapper.classList.add('d-none');
        empty.classList.add('d-none');
        error.classList.remove('d-none');
    }
}


function renderBookings(bookings) {
    const tableBody = document.getElementById('bookingsTableBody');
    const empty = document.getElementById('bookingsEmpty');
    const tableWrapper = document.getElementById('bookingsTableWrapper');

    tableBody.innerHTML = '';

    if (!bookings.length) {
        tableWrapper.classList.add('d-none');
        empty.classList.remove('d-none');
        return;
    }

    empty.classList.add('d-none');
    tableWrapper.classList.remove('d-none');

    bookings.forEach((booking) => {
        const row = document.createElement('tr');

        const startDate = new Date(booking.start_time.replace(' ', 'T'));
        const endDate = new Date(booking.end_time.replace(' ', 'T'));

        const dateText = formatBookingDate(startDate);
        const timeText = `${formatBookingTime(startDate)} - ${formatBookingTime(endDate)}`;

        const organizer = booking.organizer_name || `User #${booking.user_id}`;
        const room = booking.room_name || `Room #${booking.room_id}`;

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
                    ${escapeHtml(room)}
                </div>
                ${
                    booking.room_code
                        ? `<div class="booking-room-code">${escapeHtml(booking.room_code)}</div>`
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
                ${renderBookingStatus(booking.status)}
            </td>
        `;

        tableBody.appendChild(row);
    });
}


function setupBookingFilters() {
    const searchInput = document.getElementById('bookingSearch');
    const statusFilter = document.getElementById('statusFilter');

    if (searchInput) {
        searchInput.addEventListener('input', applyBookingFilters);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', applyBookingFilters);
    }
}


function applyBookingFilters() {
    const searchInput = document.getElementById('bookingSearch');
    const statusFilter = document.getElementById('statusFilter');

    const searchTerm = searchInput
        ? searchInput.value.trim().toLowerCase()
        : '';

    const selectedStatus = statusFilter
        ? statusFilter.value.toLowerCase()
        : '';

    const filteredBookings = allBookings.filter((booking) => {
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
            !searchTerm || searchableText.includes(searchTerm);

        const matchesStatus =
            !selectedStatus ||
            String(booking.status).toLowerCase() === selectedStatus;

        return matchesSearch && matchesStatus;
    });

    renderBookings(filteredBookings);
}


/* ==========================================================================
   Booking Helpers
   ========================================================================== */

function formatBookingDate(date) {
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}


function formatBookingTime(date) {
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleTimeString('en-IN', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}


function renderBookingStatus(status) {
    const normalizedStatus = String(status || 'pending').toLowerCase();

    const label = normalizedStatus.charAt(0).toUpperCase()
        + normalizedStatus.slice(1);

    return `
        <span class="booking-status booking-status-${escapeHtml(normalizedStatus)}">
            ${escapeHtml(label)}
        </span>
    `;
}


function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}