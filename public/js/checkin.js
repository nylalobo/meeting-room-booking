/**
 * MeetSpace Enterprise Suite - Room QR Check-in Page Logic
 *
 * Handles:
 * - Mobile-first QR check-in & check-out actions
 * - Instant DOM state transitions with loading states
 * - Error & conflict handling (401, 403, 409, 422)
 * - Dynamic room status refresh via /api/rooms/{id}/current-booking
 * - Anti-flash theme synchronization & toggling
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', initCheckin);

    function initCheckin() {
        initThemeToggle();
        initCheckinActions();
        initRefreshAction();
    }

    /* ------------------------------------------------------------------------
       1. Theme Toggle & Synchronization
       ------------------------------------------------------------------------ */
    function initThemeToggle() {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        if (!themeToggleBtn) return;

        function updateThemeIcon(theme) {
            if (!themeIcon) return;
            if (theme === 'light') {
                themeIcon.className = 'bi bi-sun';
                themeToggleBtn.setAttribute('aria-label', 'Switch to dark theme');
                themeToggleBtn.title = 'Switch to dark theme';
            } else {
                themeIcon.className = 'bi bi-moon-stars';
                themeToggleBtn.setAttribute('aria-label', 'Switch to light theme');
                themeToggleBtn.title = 'Switch to light theme';
            }
        }

        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        updateThemeIcon(currentTheme);

        themeToggleBtn.addEventListener('click', function() {
            const nowTheme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            const nextTheme = nowTheme === 'light' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', nextTheme);
            try {
                localStorage.setItem('meetspace-theme', nextTheme);
            } catch (e) {
                // Ignore storage errors in private browsing
            }
            updateThemeIcon(nextTheme);
        });
    }

    /* ------------------------------------------------------------------------
       2. Alert UI Helper
       ------------------------------------------------------------------------ */
    function showAlert(type, message, isDismissible) {
        const alertEl = document.getElementById('checkinAlert');
        if (!alertEl) return;

        alertEl.className = `alert alert-${type} d-flex align-items-center gap-2 mb-3`;

        let icon = 'bi-info-circle-fill';
        if (type === 'success') icon = 'bi-check-circle-fill';
        else if (type === 'danger') icon = 'bi-exclamation-octagon-fill';
        else if (type === 'warning') icon = 'bi-exclamation-triangle-fill';

        const dismissBtn = isDismissible ? '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>' : '';

        alertEl.innerHTML = `
            <i class="bi ${icon} flex-shrink-0 fs-5"></i>
            <div class="flex-grow-1">${message}</div>
            ${dismissBtn}
        `;

        alertEl.classList.remove('d-none');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        const alertEl = document.getElementById('checkinAlert');
        if (alertEl) {
            alertEl.classList.add('d-none');
        }
    }

    /* ------------------------------------------------------------------------
       3. Check-In & Check-Out Actions
       ------------------------------------------------------------------------ */
    function initCheckinActions() {
        const btnCheckIn = document.getElementById('btnCheckIn');
        const btnCheckOut = document.getElementById('btnCheckOut');

        const stateNotCheckedIn = document.getElementById('stateNotCheckedIn');
        const stateCheckedIn = document.getElementById('stateCheckedIn');
        const stateCheckedOut = document.getElementById('stateCheckedOut');
        const checkedInTimestamp = document.getElementById('checkedInTimestamp');
        const checkedOutTimestamp = document.getElementById('checkedOutTimestamp');

        if (btnCheckIn) {
            btnCheckIn.addEventListener('click', async function() {
                hideAlert();
                const bookingId = btnCheckIn.dataset.bookingId || document.body.dataset.bookingId;

                if (!bookingId) {
                    showAlert('danger', 'Booking information is missing. Please refresh the page.');
                    return;
                }

                // Loading state
                const originalHtml = btnCheckIn.innerHTML;
                btnCheckIn.disabled = true;
                btnCheckIn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Checking In...';

                try {
                    const response = await fetch(`/api/bookings/${encodeURIComponent(bookingId)}/check-in`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ check_in_method: 'qr_code' })
                    });

                    const result = await response.json().catch(() => ({}));

                    if (response.status === 201) {
                        // Success
                        showAlert('success', 'You have successfully checked in to this meeting!', true);

                        if (stateNotCheckedIn) stateNotCheckedIn.classList.add('d-none');
                        if (stateCheckedIn) stateCheckedIn.classList.remove('d-none');

                        if (checkedInTimestamp) {
                            const timeStr = result.data && result.data.check_in_time
                                ? formatDateTime(result.data.check_in_time)
                                : 'just now';
                            checkedInTimestamp.textContent = `Checked in at ${timeStr}`;
                        }
                    } else if (response.status === 409) {
                        // Already checked in
                        showAlert('warning', escapeHtml(result.message) || 'You are already checked in to this meeting.');
                        if (stateNotCheckedIn) stateNotCheckedIn.classList.add('d-none');
                        if (stateCheckedIn) stateCheckedIn.classList.remove('d-none');
                    } else if (response.status === 401) {
                        // Session expired
                        showAlert('danger', 'Your session has expired. <a href="/login" class="alert-link">Please sign in again</a>.');
                        btnCheckIn.disabled = false;
                        btnCheckIn.innerHTML = originalHtml;
                    } else if (response.status === 403) {
                        showAlert('danger', escapeHtml(result.message) || 'You are not authorized to check in to this meeting.');
                        btnCheckIn.disabled = false;
                        btnCheckIn.innerHTML = originalHtml;
                    } else if (response.status === 422) {
                        showAlert('warning', escapeHtml(result.message) || 'Check-in is not currently available for this meeting.');
                        btnCheckIn.disabled = false;
                        btnCheckIn.innerHTML = originalHtml;
                    } else {
                        showAlert('danger', escapeHtml(result.message) || 'An unexpected error occurred. Please try again.');
                        btnCheckIn.disabled = false;
                        btnCheckIn.innerHTML = originalHtml;
                    }
                } catch (err) {
                    showAlert('danger', 'Network error. Please check your connection and try again.');
                    btnCheckIn.disabled = false;
                    btnCheckIn.innerHTML = originalHtml;
                }
            });
        }

        if (btnCheckOut) {
            btnCheckOut.addEventListener('click', async function() {
                hideAlert();
                const bookingId = btnCheckOut.dataset.bookingId || document.body.dataset.bookingId;

                if (!bookingId) {
                    showAlert('danger', 'Booking information is missing. Please refresh the page.');
                    return;
                }

                // Loading state
                const originalHtml = btnCheckOut.innerHTML;
                btnCheckOut.disabled = true;
                btnCheckOut.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Checking Out...';

                try {
                    const response = await fetch(`/api/bookings/${encodeURIComponent(bookingId)}/check-out`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json().catch(() => ({}));

                    if (response.status === 200) {
                        showAlert('success', 'You have successfully checked out of this meeting.', true);

                        if (stateCheckedIn) stateCheckedIn.classList.add('d-none');
                        if (stateCheckedOut) stateCheckedOut.classList.remove('d-none');

                        if (checkedOutTimestamp) {
                            const timeStr = result.data && result.data.check_out_time
                                ? formatDateTime(result.data.check_out_time)
                                : 'just now';
                            checkedOutTimestamp.textContent = `Checked out at ${timeStr}`;
                        }
                    } else if (response.status === 400) {
                        showAlert('warning', escapeHtml(result.message) || 'Check-out cannot be completed for this meeting.');
                        btnCheckOut.disabled = false;
                        btnCheckOut.innerHTML = originalHtml;
                    } else if (response.status === 401) {
                        showAlert('danger', 'Your session has expired. <a href="/login" class="alert-link">Please sign in again</a>.');
                        btnCheckOut.disabled = false;
                        btnCheckOut.innerHTML = originalHtml;
                    } else {
                        showAlert('danger', escapeHtml(result.message) || 'Unable to check out. Please try again.');
                        btnCheckOut.disabled = false;
                        btnCheckOut.innerHTML = originalHtml;
                    }
                } catch (err) {
                    showAlert('danger', 'Network error. Please check your connection and try again.');
                    btnCheckOut.disabled = false;
                    btnCheckOut.innerHTML = originalHtml;
                }
            });
        }
    }

    /* ------------------------------------------------------------------------
       4. Dynamic Room Status Refresh
       ------------------------------------------------------------------------ */
    function initRefreshAction() {
        const refreshBtn = document.getElementById('checkinRefreshBtn');
        if (!refreshBtn) return;

        refreshBtn.addEventListener('click', async function() {
            const roomId = document.body.dataset.roomId;
            if (!roomId) return;

            const icon = refreshBtn.querySelector('i');
            if (icon) icon.classList.add('checkin-spin');
            refreshBtn.disabled = true;

            try {
                const response = await fetch(`/api/rooms/${encodeURIComponent(roomId)}/current-booking`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.status === 200) {
                    const result = await response.json();
                    const booking = result.data ? (result.data.booking || (result.data.id ? result.data : null)) : null;
                    const currentBookingId = document.body.dataset.bookingId || '';

                    // If booking presence changed or new booking ID arrived, refresh page to rebuild full authorized state
                    const newBookingId = booking && booking.id ? String(booking.id) : '';
                    if (newBookingId !== currentBookingId) {
                        window.location.reload();
                        return;
                    }

                    showAlert('info', 'Room schedule is up to date.', true);
                } else {
                    showAlert('warning', 'Could not refresh room schedule. Please try again.');
                }
            } catch (e) {
                showAlert('danger', 'Network error while refreshing room schedule.');
            } finally {
                if (icon) icon.classList.remove('checkin-spin');
                refreshBtn.disabled = false;
            }
        });
    }

    /* ------------------------------------------------------------------------
       5. Format Helpers
       ------------------------------------------------------------------------ */
    function formatDateTime(dateStr) {
        try {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) + ', ' +
                   d.toLocaleDateString([], { month: 'short', day: 'numeric' });
        } catch (e) {
            return dateStr;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

})();
