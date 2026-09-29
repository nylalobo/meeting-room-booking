<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Register' ?> - MeetSpace Enterprise Suite</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/meetspace-icon.png') ?>">

    <style>
        :root {
            --bg-primary: #080D1A;
            --bg-secondary: #0D1424;
            --surface: #111A2E;
            --surface-elevated: #16213A;
            --primary: #6366F1;
            --primary-hover: #818CF8;
            --secondary: #7C3AED;
            --accent-blue: #38BDF8;
            --text-primary: #F8FAFC;
            --text-secondary: #CBD5E1;
            --text-muted: #94A3B8;
            --border-glass: rgba(129, 140, 248, 0.16);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-focus: #6366F1;
            --color-text-white: #F8FAFC;
            --color-text-muted: #94A3B8;
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-primary);
            color: var(--text-primary, #F8FAFC);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 20px;
            position: relative;
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 18% 18%, rgba(99, 102, 241, 0.15) 0%, transparent 42%),
                radial-gradient(circle at 82% 82%, rgba(56, 189, 248, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 50% 10%, rgba(124, 58, 237, 0.08) 0%, transparent 35%);
            animation: authPageFadeIn 0.3s ease-out;
        }

        @keyframes authPageFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Ambient Depth Orbs */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.6;
        }

        .ambient-orb-1 {
            width: 340px;
            height: 340px;
            top: -60px;
            left: -60px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, rgba(124, 58, 237, 0.1) 70%, transparent 100%);
        }

        .ambient-orb-2 {
            width: 380px;
            height: 380px;
            bottom: -80px;
            right: -80px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, rgba(99, 102, 241, 0.08) 70%, transparent 100%);
        }

        .register-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 1;
            animation: authCardEntrance 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes authCardEntrance {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .register-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
            text-decoration: none;
        }

        .brand-logo-box {
            width: 44px;
            height: 44px;
            background: #0D1424;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(99, 102, 241, 0.32);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 16px rgba(99, 102, 241, 0.2);
        }

        .brand-logo-crop {
            position: absolute;
            width: 224px;
            height: 88px;
            max-width: none;
            left: -37px;
            top: -21px;
            display: block;
            pointer-events: none;
        }

        .brand-info {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .brand-title-accent {
            background: linear-gradient(135deg, #6366F1 0%, #38BDF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #6366F1;
        }

        .brand-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: #7e8ea6;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-top: 2px;
            line-height: 1.2;
        }

        .register-card {
            background: rgba(17, 26, 46, 0.72);
            border: 1px solid var(--border-glass, rgba(129, 140, 248, 0.16));
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.55), 0 0 24px rgba(99, 102, 241, 0.08);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .register-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .register-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .register-description {
            font-size: 13.5px;
            color: var(--color-text-muted);
            line-height: 1.4;
        }

        .alert-custom {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.28);
            color: #fca5a5;
        }

        .alert-error i {
            font-size: 16px;
            color: #ef4444;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .form-row {
            display: flex;
            gap: 16px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 7px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 15px;
            pointer-events: none;
            transition: color 0.15s ease;
        }

        .form-control-custom {
            width: 100%;
            background: #0D1424;
            border: 1px solid rgba(129, 140, 248, 0.2);
            border-radius: 10px;
            color: var(--text-primary, #F8FAFC);
            font-size: 14px;
            padding: 11px 16px 11px 40px;
            outline: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
        }

        .form-control-custom:focus {
            border-color: var(--primary, #6366F1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25), 0 0 16px rgba(99, 102, 241, 0.15);
            background: #111A2E;
        }

        .form-control-custom:focus + .input-icon,
        .input-group-custom:focus-within .input-icon {
            color: #38bdf8;
        }

        .form-control-custom::placeholder {
            color: #475569;
        }

        select.form-control-custom {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            padding-right: 36px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        select.form-control-custom:focus {
            border-color: var(--primary, #6366F1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25), 0 0 16px rgba(99, 102, 241, 0.15);
            background: #111A2E;
        }

        select.form-control-custom option {
            background-color: #0f1535;
            color: #ffffff;
            padding: 8px 12px;
        }

        .label-optional {
            font-size: 11.5px;
            font-weight: 500;
            color: #64748b;
            margin-left: 4px;
        }

        .field-help {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 5px;
            display: block;
        }

        .form-control-custom.is-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
        }

        .invalid-feedback-custom {
            display: none;
            font-size: 12px;
            font-weight: 500;
            color: #f87171;
            margin-top: 6px;
            line-height: 1.35;
        }

        .invalid-feedback-custom.active {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, #6366F1 0%, #7C3AED 100%);
            color: #FFFFFF;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
            margin-top: 24px;
            text-decoration: none;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #4F46E5 0%, #6D28D9 100%);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5), 0 0 12px rgba(56, 189, 248, 0.25);
            transform: translateY(-2px);
            color: #FFFFFF;
            text-decoration: none;
        }

        .btn-register:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .login-switch {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        .login-switch a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
        }

        .login-switch a:hover {
            text-decoration: underline;
        }

        .register-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        @media (prefers-reduced-motion: reduce) {
            body {
                animation: none !important;
            }
            .auth-container, .register-container, .login-container, .resend-container, .status-container {
                animation: none !important;
            }
            .btn-signin, .btn-register, .btn-primary-auth, .btn-submit, .btn-action {
                transition: none !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body>

<div class="ambient-orb ambient-orb-1" aria-hidden="true"></div>
<div class="ambient-orb ambient-orb-2" aria-hidden="true"></div>

<div class="register-container">

    <!-- MeetSpace Branding -->
    <div class="register-brand">
        <div class="brand-logo-box">
            <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace Logo" class="brand-logo-crop">
        </div>
        <div class="brand-info">
            <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
            <div class="brand-subtitle">Enterprise Suite</div>
        </div>
    </div>

    <!-- Register Card -->
    <div class="register-card">

        <div class="register-header">
            <h1 class="register-title">Create an Account</h1>
            <p class="register-description">Register to manage and book meeting spaces across your enterprise.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-error" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= esc($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors) && is_array($errors)): ?>
            <div class="alert-custom alert-error" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= esc($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form id="registerForm" action="<?= base_url('register') ?>" method="POST" autocomplete="on" novalidate>
            <?= csrf_field() ?>

            <!-- Name Row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <div class="input-group-custom">
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="form-control-custom"
                            placeholder="John"
                            required
                            autofocus
                            value="<?= esc(old('first_name')) ?>"
                            autocomplete="given-name"
                            aria-describedby="first_name_error"
                        >
                        <i class="bi bi-person input-icon"></i>
                    </div>
                    <div id="first_name_error" class="invalid-feedback-custom" role="alert"></div>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <div class="input-group-custom">
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="form-control-custom"
                            placeholder="Doe"
                            required
                            value="<?= esc(old('last_name')) ?>"
                            autocomplete="family-name"
                            aria-describedby="last_name_error"
                        >
                        <i class="bi bi-person input-icon"></i>
                    </div>
                    <div id="last_name_error" class="invalid-feedback-custom" role="alert"></div>
                </div>
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label for="email" class="form-label">Corporate Email Address <span class="text-danger">*</span></label>
                <div class="input-group-custom">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control-custom"
                        placeholder="john.doe@enterprise.com"
                        required
                        value="<?= esc(old('email')) ?>"
                        autocomplete="email"
                        aria-describedby="email_error"
                    >
                    <i class="bi bi-envelope input-icon"></i>
                </div>
                <div id="email_error" class="invalid-feedback-custom" role="alert"></div>
            </div>

            <!-- Phone Number (Optional) -->
            <div class="form-group">
                <label for="phone" class="form-label">Phone Number <span class="label-optional">(Optional)</span></label>
                <div class="input-group-custom">
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control-custom"
                        placeholder="+1 (555) 000-0000"
                        value="<?= esc(old('phone')) ?>"
                        autocomplete="tel"
                    >
                    <i class="bi bi-telephone input-icon"></i>
                </div>
            </div>

            <!-- Role & Department Row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="role_id" class="form-label">Company Role <span class="text-danger">*</span></label>
                    <div class="input-group-custom">
                        <select
                            id="role_id"
                            name="role_id"
                            class="form-control-custom"
                            required
                            aria-describedby="role_id_error"
                        >
                            <option value="" disabled <?= old('role_id') ? '' : 'selected' ?>>Select role...</option>
                            <?php if (!empty($allowedRoles)): ?>
                                <?php foreach ($allowedRoles as $role): ?>
                                    <option value="<?= esc($role['id']) ?>" <?= (string) old('role_id') === (string) $role['id'] ? 'selected' : '' ?>>
                                        <?= esc($role['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="4" selected>Employee</option>
                            <?php endif; ?>
                        </select>
                        <i class="bi bi-briefcase input-icon"></i>
                    </div>
                    <div id="role_id_error" class="invalid-feedback-custom" role="alert"></div>
                </div>

                <div class="form-group">
                    <label for="department_id" class="form-label">Department <span class="label-optional">(Optional)</span></label>
                    <div class="input-group-custom">
                        <select
                            id="department_id"
                            name="department_id"
                            class="form-control-custom"
                        >
                            <option value="" <?= old('department_id') ? '' : 'selected' ?>>Unassigned</option>
                            <?php if (!empty($departments)): ?>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= esc($dept['id']) ?>" <?= (string) old('department_id') === (string) $dept['id'] ? 'selected' : '' ?>>
                                        <?= esc($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <i class="bi bi-building input-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-custom"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                        aria-describedby="password_error"
                    >
                    <i class="bi bi-lock input-icon"></i>
                </div>
                <div id="password_error" class="invalid-feedback-custom" role="alert"></div>
                <span class="field-help">Must be at least 8 characters with 1 uppercase letter and 1 number.</span>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        class="form-control-custom"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                        aria-describedby="password_confirm_error"
                    >
                    <i class="bi bi-shield-check input-icon"></i>
                </div>
                <div id="password_confirm_error" class="invalid-feedback-custom" role="alert"></div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-register" id="submitRegisterBtn">
                <i class="bi bi-person-plus-fill"></i>
                Create Account
            </button>
        </form>

        <div class="login-switch">
            Already have an account? <a href="<?= base_url('login') ?>">Sign In</a>
        </div>

    </div>

    <div class="register-footer">
        &copy; <?= date('Y') ?> MeetSpace Enterprise Suite &bull; Secure Room & Resource Management
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role_id');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');

    const firstNameError = document.getElementById('first_name_error');
    const lastNameError = document.getElementById('last_name_error');
    const emailError = document.getElementById('email_error');
    const roleError = document.getElementById('role_id_error');
    const passwordError = document.getElementById('password_error');
    const confirmError = document.getElementById('password_confirm_error');

    function isValidEmail(val) {
        if (!val || typeof val !== 'string') return false;
        val = val.trim();
        const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(val)) return false;
        const domain = val.split('@')[1] || '';
        if (/\.([a-zA-Z0-9-]+)\.\1$/i.test(domain)) return false;
        return true;
    }

    function setFieldError(input, errorEl, message) {
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
        if (errorEl) {
            errorEl.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + message;
            errorEl.classList.add('active');
        }
    }

    function clearFieldError(input, errorEl) {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
        if (errorEl) {
            errorEl.innerHTML = '';
            errorEl.classList.remove('active');
        }
    }

    // Dynamic field cleanup on input / change
    if (firstNameInput) {
        firstNameInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim().length > 0) {
                clearFieldError(this, firstNameError);
            }
        });
    }

    if (lastNameInput) {
        lastNameInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim().length > 0) {
                clearFieldError(this, lastNameError);
            }
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                const val = this.value.trim();
                if (val.length > 0 && isValidEmail(val)) {
                    clearFieldError(this, emailError);
                }
            }
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            if (this.value) {
                clearFieldError(this, roleError);
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                const val = this.value;
                if (val.length >= 8 && /[A-Z]/.test(val) && /[0-9]/.test(val)) {
                    clearFieldError(this, passwordError);
                }
            }
            if (confirmInput && confirmInput.classList.contains('is-invalid') && confirmInput.value === this.value) {
                clearFieldError(confirmInput, confirmError);
            }
        });
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                if (passwordInput && this.value === passwordInput.value) {
                    clearFieldError(this, confirmError);
                }
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let hasError = false;
            let firstInvalid = null;

            // Clear previous errors
            clearFieldError(firstNameInput, firstNameError);
            clearFieldError(lastNameInput, lastNameError);
            clearFieldError(emailInput, emailError);
            if (roleSelect) clearFieldError(roleSelect, roleError);
            clearFieldError(passwordInput, passwordError);
            clearFieldError(confirmInput, confirmError);

            // First Name
            const fnVal = firstNameInput ? firstNameInput.value.trim() : '';
            if (!fnVal) {
                setFieldError(firstNameInput, firstNameError, 'First name is required.');
                hasError = true;
                if (!firstInvalid) firstInvalid = firstNameInput;
            } else if (fnVal.length > 100) {
                setFieldError(firstNameInput, firstNameError, 'First name cannot exceed 100 characters.');
                hasError = true;
                if (!firstInvalid) firstInvalid = firstNameInput;
            }

            // Last Name
            const lnVal = lastNameInput ? lastNameInput.value.trim() : '';
            if (!lnVal) {
                setFieldError(lastNameInput, lastNameError, 'Last name is required.');
                hasError = true;
                if (!firstInvalid) firstInvalid = lastNameInput;
            } else if (lnVal.length > 100) {
                setFieldError(lastNameInput, lastNameError, 'Last name cannot exceed 100 characters.');
                hasError = true;
                if (!firstInvalid) firstInvalid = lastNameInput;
            }

            // Email
            const emailVal = emailInput ? emailInput.value.trim() : '';
            if (!emailVal) {
                setFieldError(emailInput, emailError, 'Email address is required.');
                hasError = true;
                if (!firstInvalid) firstInvalid = emailInput;
            } else if (!isValidEmail(emailVal)) {
                setFieldError(emailInput, emailError, 'Please provide a valid email address.');
                hasError = true;
                if (!firstInvalid) firstInvalid = emailInput;
            } else if (emailVal.length > 255) {
                setFieldError(emailInput, emailError, 'Email address cannot exceed 255 characters.');
                hasError = true;
                if (!firstInvalid) firstInvalid = emailInput;
            }

            // Company Role
            if (roleSelect && !roleSelect.value) {
                setFieldError(roleSelect, roleError, 'Please select a company role.');
                hasError = true;
                if (!firstInvalid) firstInvalid = roleSelect;
            }

            // Password
            const pwVal = passwordInput ? passwordInput.value : '';
            if (!pwVal) {
                setFieldError(passwordInput, passwordError, 'Password is required.');
                hasError = true;
                if (!firstInvalid) firstInvalid = passwordInput;
            } else if (pwVal.length < 8) {
                setFieldError(passwordInput, passwordError, 'Password must be at least 8 characters long.');
                hasError = true;
                if (!firstInvalid) firstInvalid = passwordInput;
            } else if (!/[A-Z]/.test(pwVal)) {
                setFieldError(passwordInput, passwordError, 'Password must contain at least one uppercase letter.');
                hasError = true;
                if (!firstInvalid) firstInvalid = passwordInput;
            } else if (!/[0-9]/.test(pwVal)) {
                setFieldError(passwordInput, passwordError, 'Password must contain at least one number.');
                hasError = true;
                if (!firstInvalid) firstInvalid = passwordInput;
            }

            // Password Confirm
            const confirmVal = confirmInput ? confirmInput.value : '';
            if (!confirmVal) {
                setFieldError(confirmInput, confirmError, 'Please confirm your password.');
                hasError = true;
                if (!firstInvalid) firstInvalid = confirmInput;
            } else if (pwVal && confirmVal !== pwVal) {
                setFieldError(confirmInput, confirmError, 'Passwords do not match.');
                hasError = true;
                if (!firstInvalid) firstInvalid = confirmInput;
            }

            if (hasError) {
                e.preventDefault();
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    }
});
</script>
</body>
</html>
