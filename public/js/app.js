/**
 * MeetSpace Enterprise Suite - Application JavaScript
 *
 * Core / Global functionality:
 * - In-page notification system
 * - Custom confirmation modal dialog
 * - Global search input handler
 * - Shared helper utilities (escapeHtml)
 */

document.addEventListener('DOMContentLoaded', () => {

    // Inject notification / confirmation UI
    initializeAppNotifications();

    // UI/UX feature initializations
    initializeThemeToggle();
    initializeSidebarToggle();
    initializeLogoutConfirmation();

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

            background: rgba(17, 26, 46, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(129, 140, 248, 0.18);

            border-radius: 12px;

            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.45);

            transform: translateX(120%);
            opacity: 0;

            transition:
                transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.35s ease;

            pointer-events: auto;
        }

        .app-notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .app-notification.success {
            border-left: 4px solid #10b981;
        }

        .app-notification.error {
            border-left: 4px solid #ef4444;
        }

        .app-notification.warning {
            border-left: 4px solid #f59e0b;
        }

        .app-notification.info {
            border-left: 4px solid #38bdf8;
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
            color: #34d399;
        }

        .app-notification.error
        .app-notification-icon {
            color: #fb7185;
        }

        .app-notification.warning
        .app-notification-icon {
            color: #fbbf24;
        }

        .app-notification.info
        .app-notification-icon {
            color: #38bdf8;
        }

        .app-notification-content {
            flex: 1;
            min-width: 0;
        }

        .app-notification-title {
            color: #f8fafc;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .app-notification-message {
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.5;
        }

        .app-notification-close {
            border: 0;
            background: transparent;

            color: #94a3b8;

            cursor: pointer;

            font-size: 14px;

            padding: 4px;

            transition:
                color 0.2s ease;
        }

        .app-notification-close:hover {
            color: #f8fafc;
        }

        [data-theme="light"] .app-notification {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12), 0 4px 12px rgba(15, 23, 42, 0.05);
        }

        [data-theme="light"] .app-notification-title {
            color: #0f172a;
        }

        [data-theme="light"] .app-notification-message {
            color: #334155;
        }

        [data-theme="light"] .app-notification-close {
            color: #64748b;
        }

        [data-theme="light"] .app-notification-close:hover {
            color: #0f172a;
        }

        [data-theme="light"] .app-notification.success {
            border-left: 4px solid #059669;
        }

        [data-theme="light"] .app-notification.success .app-notification-icon {
            color: #059669;
        }

        [data-theme="light"] .app-notification.error {
            border-left: 4px solid #dc2626;
        }

        [data-theme="light"] .app-notification.error .app-notification-icon {
            color: #dc2626;
        }

        [data-theme="light"] .app-notification.warning {
            border-left: 4px solid #d97706;
        }

        [data-theme="light"] .app-notification.warning .app-notification-icon {
            color: #d97706;
        }

        [data-theme="light"] .app-notification.info {
            border-left: 4px solid #2563eb;
        }

        [data-theme="light"] .app-notification.info .app-notification-icon {
            color: #2563eb;
        }

        @media (prefers-reduced-motion: reduce) {
            .app-notification {
                transition: opacity 0.15s ease !important;
                transform: none !important;
            }
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
                rgba(3, 7, 18, 0.78);

            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .app-confirm-modal {
            width: min(440px, 100%);

            background: rgba(17, 24, 39, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);

            border:
                1px solid rgba(255,255,255,0.12);

            border-radius: 16px;

            padding: 28px;

            box-shadow:
                0 30px 80px rgba(0,0,0,0.55);

            animation:
                appConfirmAppear 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
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
    notification.setAttribute(
        'role',
        type === 'error' || type === 'warning' ? 'alert' : 'status'
    );
    notification.setAttribute(
        'aria-live',
        'polite'
    );

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


/* ==========================================================================
   UI / UX ENHANCEMENTS (THEME, SIDEBAR, LOGOUT)
   ========================================================================== */

/**
 * Global MeetSpace Loading Indicator (Uiverse Inspired)
 * Generates reusable progress-bar loader markup with animated track,
 * highlight bars, and staggered typography dots.
 *
 * @param {string} labelText - Text description (e.g. "Loading bookings")
 * @returns {string} HTML string
 */
window.createMeetSpaceLoader = function(labelText = 'Loading') {
    const cleanLabel = String(labelText).replace(/\.{3,}$/, '').trim();
    return `
        <div class="meetspace-loader" role="status" aria-live="polite">
            <div class="meetspace-loader-track">
                <div class="meetspace-loader-bar">
                    <div class="meetspace-loader-highlights"></div>
                </div>
            </div>
            <div class="meetspace-loader-text">
                <span class="loader-label">${cleanLabel}</span><span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    `;
};

/**
 * Light / Dark mode theme toggle
 * Supports instant anti-flash rendering and persists selection in localStorage.
 * Integrates Cosmic Toggle animations.
 */
function initializeThemeToggle() {
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeLabel = document.getElementById('themeLabel');

    function applyTheme(theme, persist = true) {
        const activeTheme = theme === 'light' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', activeTheme);
        if (themeLabel) {
            themeLabel.textContent = activeTheme === 'light' ? 'Light' : 'Dark';
        }
        if (themeToggleBtn) {
            const nextMode = activeTheme === 'light' ? 'dark' : 'light';
            themeToggleBtn.setAttribute('aria-label', `Switch to ${nextMode} theme`);
            themeToggleBtn.title = `Switch to ${nextMode} theme`;
            themeToggleBtn.setAttribute('aria-checked', activeTheme === 'dark' ? 'true' : 'false');
        }
        if (persist) {
            try {
                localStorage.setItem('meetspace-theme', activeTheme);
            } catch (err) {
                // Ignore localStorage errors (e.g. private mode)
            }
        }
    }

    // Determine initial active theme
    const currentTheme = document.documentElement.getAttribute('data-theme')
        || (function() {
            try { return localStorage.getItem('meetspace-theme'); } catch(e) { return null; }
        })()
        || 'dark';

    applyTheme(currentTheme, false);

    let themeTransitionTimer = null;

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const nowTheme = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            const nextTheme = nowTheme === 'light' ? 'dark' : 'light';
            document.documentElement.classList.add('theme-transitioning');
            if (themeTransitionTimer) {
                clearTimeout(themeTransitionTimer);
            }
            themeTransitionTimer = setTimeout(() => {
                document.documentElement.classList.remove('theme-transitioning');
            }, 260);
            applyTheme(nextTheme, true);
        });
    }
}

/**
 * Responsive sidebar navigation & collapse
 * - On desktop (>= 992px): toggles compact icon-only collapsed state (.sidebar-collapsed on #appWrapper)
 * - On mobile (< 992px): toggles off-canvas drawer (.sidebar-open on #appWrapper)
 * - Backdrop click, close button, ESC key, and link navigation dismiss drawer
 */
function initializeSidebarToggle() {
    const appWrapper = document.getElementById('appWrapper');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!appWrapper) return;

    // Restore desktop collapsed state if saved
    try {
        if (window.innerWidth >= 992 && localStorage.getItem('meetspace-sidebar-collapsed') === '1') {
            appWrapper.classList.add('sidebar-collapsed');
        }
    } catch (err) {}

    function closeMobileSidebar() {
        appWrapper.classList.remove('sidebar-open');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
    }

    function toggleSidebar() {
        if (window.innerWidth >= 992) {
            // Desktop: toggle compact icon mode
            const isCollapsed = appWrapper.classList.toggle('sidebar-collapsed');
            try {
                localStorage.setItem('meetspace-sidebar-collapsed', isCollapsed ? '1' : '0');
            } catch (err) {}
        } else {
            // Mobile: toggle drawer
            const isOpen = appWrapper.classList.toggle('sidebar-open');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeMobileSidebar);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileSidebar);
    }

    // Keyboard ESC dismissal for mobile drawer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (appWrapper.classList.contains('sidebar-open')) {
                closeMobileSidebar();
            }
        }
    });

    // Close mobile drawer on navigation link click
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-item');
    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                closeMobileSidebar();
            }
        });
    });

    // Handle responsive resize: remove mobile open state when expanding to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992 && appWrapper.classList.contains('sidebar-open')) {
            closeMobileSidebar();
        }
    });
}

/**
 * User account section logout confirmation dialog
 * Intercepts #sidebarLogoutBtn and prompts with custom showAppConfirm
 */
function initializeLogoutConfirmation() {
    const logoutBtn = document.getElementById('sidebarLogoutBtn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showAppConfirm(
            'Are you sure you want to logout of MeetSpace?',
            () => {
                window.location.href = '/logout';
            },
            'Confirm Logout',
            'Logout'
        );
    });
}

/**
 * Universal MeetSpace Server-Side Pagination Component
 *
 * @param {HTMLElement|string} target - Container element or selector
 * @param {Object} meta - Pagination metadata: { page, per_page, total, total_pages }
 * @param {Function} onPageChange - Callback when page changes: (newPage) => void
 * @param {Function} onPerPageChange - Callback when per_page changes: (newPerPage) => void
 */
function renderPagination(target, _meta, _onPageChange, _onPerPageChange) {
    const container = typeof target === 'string' ? document.querySelector(target) : target;
    if (!container) return;

    container.innerHTML = '';
    container.classList.add('d-none');
}

window.renderPagination = renderPagination;