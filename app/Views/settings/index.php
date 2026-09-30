<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Settings & Configuration</h1>
        <p class="page-subtitle">Manage system preferences, booking policies, email integration, and environment diagnostics.</p>
    </div>

    <div>
        <button type="button" class="btn-primary-action" id="headerSaveBtn">
            <i class="bi bi-floppy"></i>
            <span>Save Changes</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" id="headerSaveSpinner" role="status" aria-hidden="true"></span>
        </button>
    </div>
</div>

<form id="settingsForm" novalidate>
    <?= csrf_field() ?>

    <!-- Feedback Message Banner -->
    <div id="settingsFeedback" class="booking-form-alert d-none mb-4" role="alert" aria-live="polite">
        <i class="bi bi-check-circle" id="settingsFeedbackIcon"></i>
        <span id="settingsFeedbackText"></span>
    </div>

    <!-- 1. GENERAL SETTINGS -->
    <div class="panel-card mb-4">
        <div class="panel-header d-flex align-items-center mb-3">
            <div class="panel-icon me-2 text-primary" style="font-size: 1.25rem;">
                <i class="bi bi-sliders"></i>
            </div>
            <div>
                <h3 class="panel-title mb-0" style="font-size: 1.15rem; font-weight: 600; color: var(--color-text-white);">General Settings</h3>
                <p class="panel-subtitle mb-0 text-muted" style="font-size: 0.85rem;">Core application identity, timezone, and duration boundaries.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="booking-form-group">
                    <label for="appName" class="booking-form-label">
                        Application Name <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="appName"
                        name="app_name"
                        class="booking-form-control"
                        value="<?= esc($settings['app_name'] ?? 'MeetSpace Enterprise Suite') ?>"
                        required
                        maxlength="100"
                    >
                    <div class="invalid-feedback d-none text-danger mt-1" id="appNameFeedback" style="font-size: 12px;"></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="booking-form-group">
                    <label for="appTimezone" class="booking-form-label">
                        Application Timezone <span class="text-danger">*</span>
                    </label>
                    <select id="appTimezone" name="app_timezone" class="booking-form-control" required>
                        <?php foreach ($timezones as $tzCode => $tzLabel): ?>
                            <option value="<?= esc($tzCode) ?>" <?= ($settings['app_timezone'] ?? 'Asia/Kolkata') === $tzCode ? 'selected' : '' ?>>
                                <?= esc($tzLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback d-none text-danger mt-1" id="appTimezoneFeedback" style="font-size: 12px;"></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label for="defaultMeetingDuration" class="booking-form-label">
                        Default Meeting Duration <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input
                            type="number"
                            id="defaultMeetingDuration"
                            name="default_meeting_duration"
                            class="booking-form-control"
                            value="<?= esc($settings['default_meeting_duration'] ?? '30') ?>"
                            min="5"
                            max="480"
                            step="5"
                            required
                        >
                        <span class="input-group-text settings-unit-addon">mins</span>
                    </div>
                    <small class="text-muted" style="font-size: 11.5px;">Standard new meeting duration (5–480 min).</small>
                    <div class="invalid-feedback d-none text-danger mt-1" id="defaultMeetingDurationFeedback" style="font-size: 12px;"></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label for="maxMeetingDuration" class="booking-form-label">
                        Maximum Meeting Duration <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input
                            type="number"
                            id="maxMeetingDuration"
                            name="max_meeting_duration"
                            class="booking-form-control"
                            value="<?= esc($settings['max_meeting_duration'] ?? '240') ?>"
                            min="15"
                            max="1440"
                            step="15"
                            required
                        >
                        <span class="input-group-text settings-unit-addon">mins</span>
                    </div>
                    <small class="text-muted" style="font-size: 11.5px;">Maximum duration single session (up to 1440 min).</small>
                    <div class="invalid-feedback d-none text-danger mt-1" id="maxMeetingDurationFeedback" style="font-size: 12px;"></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label for="bookingBufferTime" class="booking-form-label">
                        Booking Buffer Time <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input
                            type="number"
                            id="bookingBufferTime"
                            name="booking_buffer_time"
                            class="booking-form-control"
                            value="<?= esc($settings['booking_buffer_time'] ?? '15') ?>"
                            min="0"
                            max="120"
                            step="5"
                            required
                        >
                        <span class="input-group-text settings-unit-addon">mins</span>
                    </div>
                    <small class="text-muted" style="font-size: 11.5px;">Rest & cleanup buffer between meetings (0–120 min).</small>
                    <div class="invalid-feedback d-none text-danger mt-1" id="bookingBufferTimeFeedback" style="font-size: 12px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BOOKING SETTINGS -->
    <div class="panel-card mb-4">
        <div class="panel-header d-flex align-items-center mb-3">
            <div class="panel-icon me-2 text-success" style="font-size: 1.25rem;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <h3 class="panel-title mb-0" style="font-size: 1.15rem; font-weight: 600; color: var(--color-text-white);">Booking Policies & Governance</h3>
                <p class="panel-subtitle mb-0 text-muted" style="font-size: 0.85rem;">Control reservation permissions, workflows, and self-service rules.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 rounded settings-inner-card">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                        <div>
                            <label class="form-check-label fw-semibold booking-room-name mb-1" for="allowRecurringMeetings" style="cursor: pointer;">
                                Allow Recurring Meetings
                            </label>
                            <p class="text-muted mb-0" style="font-size: 12px;">Enable organizers to create recurring meeting schedules (daily, weekly, monthly).</p>
                        </div>
                        <input
                            class="form-check-input ms-3"
                            type="checkbox"
                            role="switch"
                            id="allowRecurringMeetings"
                            name="allow_recurring_meetings"
                            value="1"
                            <?= (!empty($settings['allow_recurring_meetings']) && $settings['allow_recurring_meetings'] === '1') ? 'checked' : '' ?>
                            style="width: 2.75rem; height: 1.45rem; cursor: pointer;"
                        >
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded settings-inner-card">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                        <div>
                            <label class="form-check-label fw-semibold booking-room-name mb-1" for="requireBookingApproval" style="cursor: pointer;">
                                Require Booking Approval
                            </label>
                            <p class="text-muted mb-0" style="font-size: 12px;">All reservations require administrator or room manager sign-off before confirmation.</p>
                        </div>
                        <input
                            class="form-check-input ms-3"
                            type="checkbox"
                            role="switch"
                            id="requireBookingApproval"
                            name="require_booking_approval"
                            value="1"
                            <?= (!empty($settings['require_booking_approval']) && $settings['require_booking_approval'] === '1') ? 'checked' : '' ?>
                            style="width: 2.75rem; height: 1.45rem; cursor: pointer;"
                        >
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded settings-inner-card">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                        <div>
                            <label class="form-check-label fw-semibold booking-room-name mb-1" for="allowUserCancellation" style="cursor: pointer;">
                                Allow Self-Cancellation
                            </label>
                            <p class="text-muted mb-0" style="font-size: 12px;">Permit organizers to cancel their own future scheduled meetings without admin assistance.</p>
                        </div>
                        <input
                            class="form-check-input ms-3"
                            type="checkbox"
                            role="switch"
                            id="allowUserCancellation"
                            name="allow_user_cancellation"
                            value="1"
                            <?= (!empty($settings['allow_user_cancellation']) && $settings['allow_user_cancellation'] === '1') ? 'checked' : '' ?>
                            style="width: 2.75rem; height: 1.45rem; cursor: pointer;"
                        >
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded settings-inner-card">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                        <div>
                            <label class="form-check-label fw-semibold booking-room-name mb-1" for="allowUserRescheduling" style="cursor: pointer;">
                                Allow Self-Rescheduling
                            </label>
                            <p class="text-muted mb-0" style="font-size: 12px;">Permit organizers to update time slots or transfer rooms for their own active meetings.</p>
                        </div>
                        <input
                            class="form-check-input ms-3"
                            type="checkbox"
                            role="switch"
                            id="allowUserRescheduling"
                            name="allow_user_rescheduling"
                            value="1"
                            <?= (!empty($settings['allow_user_rescheduling']) && $settings['allow_user_rescheduling'] === '1') ? 'checked' : '' ?>
                            style="width: 2.75rem; height: 1.45rem; cursor: pointer;"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. EMAIL SETTINGS (ENVIRONMENT-BACKED) -->
    <div class="panel-card mb-4">
        <div class="panel-header d-flex align-items-center mb-3">
            <div class="panel-icon me-2 text-info" style="font-size: 1.25rem;">
                <i class="bi bi-envelope-at"></i>
            </div>
            <div>
                <h3 class="panel-title mb-0" style="font-size: 1.15rem; font-weight: 600; color: var(--color-text-white);">Email Settings (SMTP Integration)</h3>
                <p class="panel-subtitle mb-0 text-muted" style="font-size: 0.85rem;">Transactional email delivery parameters managed securely via server environment (.env).</p>
            </div>
        </div>

        <div class="p-3 mb-3 rounded" style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.2);">
            <div class="d-flex align-items-start">
                <i class="bi bi-shield-check text-info me-2 fs-5 mt-1"></i>
                <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.5;">
                    <strong style="color: var(--color-text-white);">Enterprise Security Policy:</strong> SMTP credentials, API keys, and authentication tokens are loaded strictly from the environment configuration (<code>.env</code>). They are encrypted in transit and never stored in the database or exposed via API or client-side code.
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-hdd-network me-1"></i> SMTP Host
                    </label>
                    <input type="text" class="booking-form-control text-secondary" value="<?= esc($email['smtp_host']) ?>" readonly disabled>
                </div>
            </div>

            <div class="col-md-2">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-ethernet me-1"></i> SMTP Port
                    </label>
                    <input type="text" class="booking-form-control text-secondary" value="<?= esc((string) $email['smtp_port']) ?>" readonly disabled>
                </div>
            </div>

            <div class="col-md-2">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-lock me-1"></i> Encryption
                    </label>
                    <input type="text" class="booking-form-control text-secondary" value="<?= esc($email['smtp_crypto']) ?>" readonly disabled>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-envelope me-1"></i> From Email
                    </label>
                    <input type="text" class="booking-form-control text-secondary" value="<?= esc($email['from_email']) ?>" readonly disabled>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-person-badge me-1"></i> From Name
                    </label>
                    <input type="text" class="booking-form-control text-secondary" value="<?= esc($email['from_name']) ?>" readonly disabled>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-key me-1"></i> SMTP Username Status
                    </label>
                    <div class="d-flex align-items-center h-100 mt-1">
                        <span class="badge py-2 px-3 rounded-pill settings-badge-configured">
                            <i class="bi bi-check2-circle me-1"></i> <?= esc($email['smtp_user_status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="booking-form-group">
                    <label class="booking-form-label text-muted">
                        <i class="bi bi-shield-lock me-1"></i> SMTP Password / Key
                    </label>
                    <div class="d-flex align-items-center h-100 mt-1">
                        <span class="badge py-2 px-3 rounded-pill settings-badge-masked">
                            <i class="bi bi-shield-fill-check me-1"></i> <?= esc($email['smtp_pass_status']) ?> (••••••••••••)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. SYSTEM INFORMATION -->
    <div class="panel-card mb-4">
        <div class="panel-header d-flex align-items-center mb-3">
            <div class="panel-icon me-2 text-warning" style="font-size: 1.25rem;">
                <i class="bi bi-cpu"></i>
            </div>
            <div>
                <h3 class="panel-title mb-0" style="font-size: 1.15rem; font-weight: 600; color: var(--color-text-white);">System Information & Runtime Diagnostics</h3>
                <p class="panel-subtitle mb-0 text-muted" style="font-size: 0.85rem;">Platform specifications and server health status.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 rounded settings-inner-card">
                    <span class="text-muted d-block mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">APPLICATION</span>
                    <strong class="booking-room-name fs-6"><?= esc($system['application']) ?></strong>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded settings-inner-card">
                    <span class="text-muted d-block mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">FRAMEWORK</span>
                    <strong class="booking-room-name fs-6"><?= esc($system['framework']) ?></strong>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded settings-inner-card">
                    <span class="text-muted d-block mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">ENVIRONMENT</span>
                    <span class="badge bg-primary px-2 py-1"><?= esc($system['environment']) ?></span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded settings-inner-card">
                    <span class="text-muted d-block mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">PHP RUNTIME</span>
                    <strong class="booking-room-name fs-6"><?= esc($system['php_version']) ?></strong>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded settings-inner-card">
                    <span class="text-muted d-block mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">DATABASE</span>
                    <strong class="booking-room-name fs-6"><?= esc($system['database']) ?></strong>
                    <div class="mt-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i> <?= esc($system['db_status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded settings-inner-card">
                    <span class="text-muted d-block mb-1" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">SERVER TIMESTAMP</span>
                    <strong class="booking-room-name fs-6" id="serverTimeDisplay"><?= esc($system['server_time']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Action Bar -->
    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary border-opacity-25">
        <span class="text-muted" style="font-size: 12.5px;">
            <i class="bi bi-info-circle me-1"></i> Changes take effect immediately upon saving.
        </span>

        <button type="submit" class="btn-primary-action" id="bottomSaveBtn">
            <i class="bi bi-floppy"></i>
            <span>Save Settings</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" id="bottomSaveSpinner" role="status" aria-hidden="true"></span>
        </button>
    </div>

</form>

<?= $this->endSection() ?>
