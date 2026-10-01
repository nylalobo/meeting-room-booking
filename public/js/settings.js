/**
 * MeetSpace Enterprise Suite - Settings & Configuration JavaScript
 *
 * Handles:
 * - Real-time client-side validation for duration constraints and required fields
 * - AJAX submission to /api/settings
 * - Visual feedback with MeetSpace toast notifications and inline banner alerts
 * - Synchronized action buttons (header and bottom save triggers)
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('settingsForm');
        if (!form) return;

        initSettingsForm();
    });

    function isSelect2Available() {
        return typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined';
    }

    function setupSettingsSelect2() {
        if (!isSelect2Available()) return;

        const $tz = $('#appTimezone');
        if ($tz.length && !$tz.hasClass('select2-hidden-accessible')) {
            $tz.select2({
                width: '100%',
                placeholder: 'Select application timezone...',
                allowClear: false
            });

            $tz.on('change', function () {
                const el = document.getElementById('appTimezone');
                if (el) clearFieldValidation(el);
            });
        }
    }

    function initSettingsForm() {
        const form = document.getElementById('settingsForm');
        const headerSaveBtn = document.getElementById('headerSaveBtn');
        const bottomSaveBtn = document.getElementById('bottomSaveBtn');

        setupSettingsSelect2();

        // Allow header button to trigger form submit
        headerSaveBtn?.addEventListener('click', () => {
            form.requestSubmit();
        });

        // Form submission
        form.addEventListener('submit', handleSettingsSubmit);

        // Real-time validation clearance
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => clearFieldValidation(input));
            input.addEventListener('change', () => clearFieldValidation(input));
        });
    }

    function clearFieldValidation(input) {
        input.classList.remove('is-invalid');
        const feedbackEl = document.getElementById(`${input.id}Feedback`);
        if (feedbackEl) {
            feedbackEl.textContent = '';
            feedbackEl.classList.add('d-none');
        }
    }

    function showFieldError(inputId, message) {
        const input = document.getElementById(inputId);
        const feedbackEl = document.getElementById(`${inputId}Feedback`);
        if (input) {
            input.classList.add('is-invalid');
        }
        if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.classList.remove('d-none');
        }
    }

    function showBannerFeedback(type, message) {
        const banner = document.getElementById('settingsFeedback');
        const icon = document.getElementById('settingsFeedbackIcon');
        const text = document.getElementById('settingsFeedbackText');
        if (!banner || !icon || !text) return;

        banner.className = 'booking-form-alert mb-4';
        if (type === 'success') {
            banner.classList.add('booking-form-success');
            icon.className = 'bi bi-check-circle-fill me-2';
        } else {
            banner.classList.add('booking-form-error');
            icon.className = 'bi bi-exclamation-triangle-fill me-2';
        }

        text.textContent = message;
        banner.classList.remove('d-none');

        // Auto-scroll to top smoothly if not visible
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideBannerFeedback() {
        const banner = document.getElementById('settingsFeedback');
        banner?.classList.add('d-none');
    }

    function setSavingState(isSaving) {
        const headerBtn = document.getElementById('headerSaveBtn');
        const bottomBtn = document.getElementById('bottomSaveBtn');
        const headerSpinner = document.getElementById('headerSaveSpinner');
        const bottomSpinner = document.getElementById('bottomSaveSpinner');

        if (headerBtn) headerBtn.disabled = isSaving;
        if (bottomBtn) bottomBtn.disabled = isSaving;

        if (headerSpinner) {
            if (isSaving) headerSpinner.classList.remove('d-none');
            else headerSpinner.classList.add('d-none');
        }
        if (bottomSpinner) {
            if (isSaving) bottomSpinner.classList.remove('d-none');
            else bottomSpinner.classList.add('d-none');
        }
    }

    async function handleSettingsSubmit(e) {
        e.preventDefault();
        hideBannerFeedback();

        const form = e.target;
        const appName = document.getElementById('appName');
        const appTimezone = document.getElementById('appTimezone');
        const defaultDuration = document.getElementById('defaultMeetingDuration');
        const maxDuration = document.getElementById('maxMeetingDuration');
        const bufferTime = document.getElementById('bookingBufferTime');

        const allowRecurring = document.getElementById('allowRecurringMeetings');
        const requireApproval = document.getElementById('requireBookingApproval');
        const allowCancel = document.getElementById('allowUserCancellation');
        const allowReschedule = document.getElementById('allowUserRescheduling');

        let hasError = false;

        // 1. Client-side Validation: Application Name
        const nameVal = (appName?.value || '').trim();
        if (!nameVal) {
            showFieldError('appName', 'Application Name is required.');
            hasError = true;
        } else if (nameVal.length < 2 || nameVal.length > 100) {
            showFieldError('appName', 'Application Name must be between 2 and 100 characters.');
            hasError = true;
        }

        // 2. Client-side Validation: Timezone
        if (!appTimezone?.value) {
            showFieldError('appTimezone', 'Please select an application timezone.');
            hasError = true;
        }

        // 3. Client-side Validation: Durations
        const defVal = parseInt(defaultDuration?.value || '0', 10);
        if (isNaN(defVal) || defVal < 5 || defVal > 480) {
            showFieldError('defaultMeetingDuration', 'Default duration must be between 5 and 480 minutes.');
            hasError = true;
        }

        const maxVal = parseInt(maxDuration?.value || '0', 10);
        if (isNaN(maxVal) || maxVal < 15 || maxVal > 1440) {
            showFieldError('maxMeetingDuration', 'Maximum duration must be between 15 and 1440 minutes.');
            hasError = true;
        } else if (!isNaN(defVal) && maxVal < defVal) {
            showFieldError('maxMeetingDuration', `Maximum duration (${maxVal}m) cannot be shorter than default duration (${defVal}m).`);
            hasError = true;
        }

        const bufVal = parseInt(bufferTime?.value || '0', 10);
        if (isNaN(bufVal) || bufVal < 0 || bufVal > 120) {
            showFieldError('bookingBufferTime', 'Buffer time must be between 0 and 120 minutes.');
            hasError = true;
        }

        if (hasError) {
            showBannerFeedback('error', 'Please resolve the highlighted validation errors before saving.');
            return;
        }

        const payload = {
            app_name: nameVal,
            app_timezone: appTimezone?.value,
            default_meeting_duration: defVal,
            max_meeting_duration: maxVal,
            booking_buffer_time: bufVal,
            allow_recurring_meetings: allowRecurring?.checked ? '1' : '0',
            require_booking_approval: requireApproval?.checked ? '1' : '0',
            allow_user_cancellation: allowCancel?.checked ? '1' : '0',
            allow_user_rescheduling: allowReschedule?.checked ? '1' : '0',
        };

        try {
            setSavingState(true);

            const response = await fetch('/api/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                if (result.errors) {
                    for (const [key, err] of Object.entries(result.errors)) {
                        // Map underscore field to camelCase element id
                        const idMap = {
                            app_name: 'appName',
                            app_timezone: 'appTimezone',
                            default_meeting_duration: 'defaultMeetingDuration',
                            max_meeting_duration: 'maxMeetingDuration',
                            booking_buffer_time: 'bookingBufferTime'
                        };
                        const targetId = idMap[key] || key;
                        showFieldError(targetId, err);
                    }
                }
                const msg = result.message || 'Unable to update settings. Please check the form and try again.';
                showBannerFeedback('error', msg);

                if (typeof showAppNotification === 'function') {
                    showAppNotification(msg, 'error', 'Settings');
                }
                return;
            }

            const successMsg = result.message || 'Settings and configurations have been successfully saved.';
            showBannerFeedback('success', successMsg);

            if (typeof showAppNotification === 'function') {
                showAppNotification(successMsg, 'success', 'Settings');
            }

        } catch (err) {
            console.error('Settings save network error:', err);
            const errMsg = 'A network error occurred while updating settings. Please try again.';
            showBannerFeedback('error', errMsg);
            if (typeof showAppNotification === 'function') {
                showAppNotification(errMsg, 'error', 'Error');
            }
        } finally {
            setSavingState(false);
        }
    }

})();
