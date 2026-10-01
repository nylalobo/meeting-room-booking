<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Register' ?> - MeetSpace Enterprise Suite</title>

    <!-- Anti-Flash Theme Script -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('meetspace-theme');
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

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

        /* Theme Toggle Button */
        .auth-theme-toggle {
            position: fixed;
            top: 20px;
            right: 24px;
            z-index: 20;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border-radius: 9999px;
            border: 1px solid rgba(129, 140, 248, 0.25);
            background: rgba(17, 26, 46, 0.82);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #F8FAFC;
            font-family: var(--font-family);
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.28);
            transition: transform 0.18s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-theme-toggle:hover {
            transform: translateY(-1px);
            border-color: rgba(129, 140, 248, 0.5);
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.22);
        }

        .auth-theme-toggle:active {
            transform: translateY(0) scale(0.98);
        }

        .auth-theme-toggle i {
            font-size: 14px;
            color: #facc15;
        }

        /* Light Mode Overrides */
        [data-theme="light"] {
            --bg-primary: #F8FAFC;
            --bg-secondary: #F1F5F9;
            --surface: #FFFFFF;
            --surface-elevated: #F8FAFC;
            --text-primary: #0F172A;
            --text-secondary: #334155;
            --text-muted: #64748B;
            --border-glass: #E2E8F0;
            --border-subtle: #E2E8F0;
            --color-text-white: #0F172A;
            --color-text-muted: #64748B;
        }

        [data-theme="light"] body {
            background-color: #F8FAFC;
            color: #0F172A;
            background-image:
                radial-gradient(circle at 18% 18%, rgba(99, 102, 241, 0.08) 0%, transparent 42%),
                radial-gradient(circle at 82% 82%, rgba(56, 189, 248, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 50% 10%, rgba(124, 58, 237, 0.05) 0%, transparent 35%);
        }

        [data-theme="light"] .ambient-orb {
            opacity: 0.22;
        }

        [data-theme="light"] .brand-title {
            color: #0F172A;
        }

        [data-theme="light"] .brand-subtitle {
            color: #64748B;
        }

        [data-theme="light"] .register-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #E2E8F0;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        [data-theme="light"] .register-title {
            color: #0F172A;
        }

        [data-theme="light"] .register-description {
            color: #475569;
        }

        [data-theme="light"] .form-label {
            color: #334155;
        }

        [data-theme="light"] .form-control-custom {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            color: #0F172A;
        }

        [data-theme="light"] .form-control-custom::placeholder {
            color: #94A3B8;
        }

        [data-theme="light"] .form-control-custom:focus,
        [data-theme="light"] select.form-control-custom:focus {
            background: #FFFFFF;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.16);
        }

        [data-theme="light"] .form-control-custom:focus + .input-icon,
        [data-theme="light"] .input-group-custom:focus-within .input-icon {
            color: #4F46E5;
        }

        [data-theme="light"] select.form-control-custom option {
            background-color: #FFFFFF;
            color: #0F172A;
        }

        [data-theme="light"] .alert-error {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #991B1B;
        }

        [data-theme="light"] .alert-error i {
            color: #DC2626;
        }

        [data-theme="light"] .label-optional,
        [data-theme="light"] .field-help,
        [data-theme="light"] .register-footer {
            color: #64748B;
        }

        [data-theme="light"] .login-switch {
            color: #475569;
        }

        [data-theme="light"] .login-switch a {
            color: #4F46E5;
        }

        [data-theme="light"] .auth-theme-toggle {
            background: rgba(255, 255, 255, 0.92);
            border-color: #CBD5E1;
            color: #0F172A;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
        }

        [data-theme="light"] .auth-theme-toggle:hover {
            background: #FFFFFF;
            border-color: #6366F1;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.15);
        }

        [data-theme="light"] .auth-theme-toggle i {
            color: #D97706;
        }

        html.theme-transitioning body,
        html.theme-transitioning .register-card,
        html.theme-transitioning .form-control-custom,
        html.theme-transitioning .auth-theme-toggle {
            transition: background-color 220ms ease, background 220ms ease, color 220ms ease, border-color 220ms ease, box-shadow 220ms ease !important;
        }

        @media (prefers-reduced-motion: reduce) {
            body {
                animation: none !important;
            }
            .auth-container, .register-container, .login-container, .resend-container, .status-container {
                animation: none !important;
            }
            .btn-signin, .btn-register, .btn-primary-auth, .btn-submit, .btn-action, .auth-theme-toggle {
                transition: none !important;
                transform: none !important;
            }
        }

        .field-error {
            display: none;
            font-size: 12px;
            font-weight: 500;
            color: #F87171;
            margin-top: 5px;
            line-height: 1.4;
        }

        .field-error.visible {
            display: block;
        }

        .form-control-custom.is-invalid {
            border-color: rgba(239, 68, 68, 0.75) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.14) !important;
        }

        [data-theme="light"] .field-error {
            color: #DC2626;
        }

        [data-theme="light"] .form-control-custom.is-invalid {
            border-color: #DC2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12) !important;
        }
    </style>
</head>
<body>

<button type="button" class="auth-theme-toggle" id="authThemeToggleBtn" aria-label="Toggle theme mode" title="Switch Theme">
    <i class="bi bi-moon-stars-fill" id="authThemeIcon"></i>
    <span id="authThemeLabel">Dark</span>
</button>

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

        <form action="<?= base_url('register') ?>" method="POST" autocomplete="on" id="registerForm">
            <?= csrf_field() ?>

            <!-- Name Row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
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
                        >
                        <i class="bi bi-person input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
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
                        >
                        <i class="bi bi-person input-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label for="email" class="form-label">Corporate Email Address</label>
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
                    >
                    <i class="bi bi-envelope input-icon"></i>
                </div>
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
                <label for="password" class="form-label">Password</label>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-custom"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    >
                    <i class="bi bi-lock input-icon"></i>
                </div>
                <span class="field-help">Must be at least 8 characters with 1 uppercase letter and 1 number.</span>
                <div class="field-error" id="passwordError" role="alert" aria-live="polite"></div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        class="form-control-custom"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    >
                    <i class="bi bi-shield-check input-icon"></i>
                </div>
                <div class="field-error" id="passwordConfirmError" role="alert" aria-live="polite"></div>
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
(function() {
    var btn = document.getElementById('authThemeToggleBtn');
    var icon = document.getElementById('authThemeIcon');
    var label = document.getElementById('authThemeLabel');
    var timer = null;

    function syncUI(theme) {
        var isLight = theme === 'light';
        if (icon) {
            icon.className = isLight ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }
        if (label) {
            label.textContent = isLight ? 'Light' : 'Dark';
        }
        if (btn) {
            var next = isLight ? 'dark' : 'light';
            btn.setAttribute('aria-label', 'Switch to ' + next + ' theme');
            btn.title = 'Switch to ' + next + ' theme';
        }
    }

    var current = document.documentElement.getAttribute('data-theme') || 'dark';
    syncUI(current);

    if (btn) {
        btn.addEventListener('click', function() {
            var now = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            var next = now === 'light' ? 'dark' : 'light';
            document.documentElement.classList.add('theme-transitioning');
            if (timer) clearTimeout(timer);
            timer = setTimeout(function() {
                document.documentElement.classList.remove('theme-transitioning');
            }, 260);
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem('meetspace-theme', next);
            } catch (e) {}
            syncUI(next);
        });
    }

    var registerForm = document.getElementById('registerForm');
    var submitRegisterBtn = document.getElementById('submitRegisterBtn');
    var passwordInput = document.getElementById('password');
    var passwordConfirmInput = document.getElementById('password_confirm');
    var passwordError = document.getElementById('passwordError');
    var passwordConfirmError = document.getElementById('passwordConfirmError');
    var confirmTouched = false;

    function setFieldError(inputEl, errorEl, message) {
        if (!inputEl || !errorEl) return;
        if (message) {
            errorEl.textContent = message;
            errorEl.classList.add('visible');
            inputEl.classList.add('is-invalid');
            inputEl.setAttribute('aria-invalid', 'true');
        } else {
            errorEl.textContent = '';
            errorEl.classList.remove('visible');
            inputEl.classList.remove('is-invalid');
            inputEl.removeAttribute('aria-invalid');
        }
    }

    function validatePassword() {
        if (!passwordInput) return true;
        var val = passwordInput.value;
        if (!val) {
            setFieldError(passwordInput, passwordError, 'Password is required.');
            return false;
        }
        if (val.length < 8) {
            setFieldError(passwordInput, passwordError, 'Password must be at least 8 characters long.');
            return false;
        }
        if (!/[A-Z]/.test(val) || !/[0-9]/.test(val)) {
            setFieldError(passwordInput, passwordError, 'Password must contain at least one uppercase letter and one number.');
            return false;
        }
        setFieldError(passwordInput, passwordError, '');
        return true;
    }

    function validatePasswordConfirm() {
        if (!passwordConfirmInput) return true;
        var confirmVal = passwordConfirmInput.value;
        var passVal = passwordInput ? passwordInput.value : '';
        if (!confirmVal) {
            setFieldError(passwordConfirmInput, passwordConfirmError, 'Please confirm your password.');
            return false;
        }
        if (confirmVal !== passVal) {
            setFieldError(passwordConfirmInput, passwordConfirmError, 'Passwords do not match.');
            return false;
        }
        setFieldError(passwordConfirmInput, passwordConfirmError, '');
        return true;
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validatePassword();
            if (confirmTouched || (passwordConfirmInput && passwordConfirmInput.value.length > 0)) {
                validatePasswordConfirm();
            }
        });
        passwordInput.addEventListener('blur', function() {
            validatePassword();
        });
    }

    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', function() {
            confirmTouched = true;
            validatePasswordConfirm();
        });
        passwordConfirmInput.addEventListener('blur', function() {
            confirmTouched = true;
            validatePasswordConfirm();
        });
    }

    if (submitRegisterBtn) {
        submitRegisterBtn.addEventListener('click', function(e) {
            confirmTouched = true;
            var passOk = validatePassword();
            var confirmOk = validatePasswordConfirm();
            if (!passOk || !confirmOk) {
                e.preventDefault();
                if (!passOk && passwordInput) {
                    passwordInput.focus();
                } else if (!confirmOk && passwordConfirmInput) {
                    passwordConfirmInput.focus();
                }
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            confirmTouched = true;
            var passOk = validatePassword();
            var confirmOk = validatePasswordConfirm();
            if (!passOk || !confirmOk) {
                e.preventDefault();
                if (!passOk && passwordInput) {
                    passwordInput.focus();
                } else if (!confirmOk && passwordConfirmInput) {
                    passwordConfirmInput.focus();
                }
            }
        });
    }
})();
</script>

</body>
</html>
