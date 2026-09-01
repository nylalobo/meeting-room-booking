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

            background: #111827;
            border: 1px solid rgba(255,255,255,0.12);

            border-radius: 12px;

            box-shadow:
                0 20px 50px rgba(0,0,0,0.35);

            transform: translateX(120%);
            opacity: 0;

            transition:
                transform 0.3s ease,
                opacity 0.3s ease;

            pointer-events: auto;
        }

        .app-notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .app-notification.success {
            border-left: 4px solid #22c55e;
        }

        .app-notification.error {
            border-left: 4px solid #ef4444;
        }

        .app-notification.warning {
            border-left: 4px solid #f59e0b;
        }

        .app-notification.info {
            border-left: 4px solid #3b82f6;
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
            color: #22c55e;
        }

        .app-notification.error
        .app-notification-icon {
            color: #ef4444;
        }

        .app-notification.warning
        .app-notification-icon {
            color: #f59e0b;
        }

        .app-notification.info
        .app-notification-icon {
            color: #3b82f6;
        }

        .app-notification-content {
            flex: 1;
            min-width: 0;
        }

        .app-notification-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .app-notification-message {
            color: #aab2d5;
            font-size: 13px;
            line-height: 1.5;
        }

        .app-notification-close {
            border: 0;
            background: transparent;

            color: #7f89ad;

            cursor: pointer;

            font-size: 14px;

            padding: 4px;

            transition:
                color 0.2s ease;
        }

        .app-notification-close:hover {
            color: #ffffff;
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
                rgba(3, 7, 18, 0.72);

            backdrop-filter: blur(4px);
        }

        .app-confirm-modal {
            width: min(440px, 100%);

            background: #111827;

            border:
                1px solid rgba(255,255,255,0.10);

            border-radius: 16px;

            padding: 28px;

            box-shadow:
                0 30px 80px rgba(0,0,0,0.50);

            animation:
                appConfirmAppear 0.2s ease;
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