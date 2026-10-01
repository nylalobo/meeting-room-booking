<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Reset Password' ?> - MeetSpace Enterprise Suite</title>
    <!-- Prevent Flash of Unstyled Theme (FOUT) -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('meetspace-theme');
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                }
            } catch (e) {}
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

        .auth-container {
            width: 100%;
            max-width: 440px;
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

        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
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
            color: var(--color-text-muted);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .auth-card {
            background: rgba(17, 26, 46, 0.72);
            border: 1px solid var(--border-glass, rgba(129, 140, 248, 0.16));
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.55), 0 0 24px rgba(99, 102, 241, 0.08);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: #c084fc;
            font-size: 24px;
        }

        .auth-title {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .auth-description {
            font-size: 13.5px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .auth-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.28);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .auth-alert i {
            font-size: 16px;
            color: #ef4444;
            flex-shrink: 0;
        }

        .auth-alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.28);
            color: #86efac;
        }

        .auth-alert-success i {
            color: #22c55e;
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

        .form-control-otp {
            width: 100%;
            background: #090e2b;
            border: 2px solid rgba(168, 85, 247, 0.35);
            border-radius: 8px;
            color: #c084fc;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 10px;
            text-align: center;
            padding: 10px 16px;
            outline: none;
            transition: all 0.15s ease;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        }

        .form-control-otp:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
            background: #070c24;
        }

        .password-hint {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 5px;
            line-height: 1.4;
        }

        .btn-primary-auth {
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

        .btn-primary-auth:hover {
            background: linear-gradient(135deg, #4F46E5 0%, #6D28D9 100%);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5), 0 0 12px rgba(56, 189, 248, 0.25);
            transform: translateY(-2px);
            color: #FFFFFF;
            text-decoration: none;
        }

        .resend-section {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .resend-text {
            font-size: 12.5px;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .btn-resend {
            background: transparent;
            border: 1px solid rgba(168, 85, 247, 0.35);
            color: #c084fc;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-resend:hover:not(:disabled) {
            background: rgba(168, 85, 247, 0.1);
            border-color: #a855f7;
        }

        .btn-resend:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: rgba(255, 255, 255, 0.1);
            color: #64748b;
        }

        .auth-footer-links {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        .auth-footer-links a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer-links a:hover {
            text-decoration: underline;
        }

        .auth-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .auth-theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 20;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border-glass, rgba(129, 140, 248, 0.16));
            background: rgba(17, 26, 46, 0.85);
            color: var(--text-secondary, #CBD5E1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .auth-theme-toggle:hover {
            border-color: rgba(99, 102, 241, 0.45);
            color: #818CF8;
            transform: translateY(-1px);
        }

        html.theme-transitioning,
        html.theme-transitioning *,
        html.theme-transitioning *::before,
        html.theme-transitioning *::after {
            transition: background-color 0.25s ease, border-color 0.25s ease, color 0.2s ease, box-shadow 0.25s ease !important;
        }

        /* Light Mode Overrides */
        [data-theme="light"] {
            --bg-primary: #F1F5F9;
            --bg-secondary: #E2E8F0;
            --surface: #FFFFFF;
            --surface-elevated: #F8FAFC;
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --secondary: #7C3AED;
            --accent-blue: #0284C7;
            --text-primary: #0F172A;
            --text-secondary: #334155;
            --text-muted: #64748B;
            --border-glass: #CBD5E1;
            --border-subtle: #E2E8F0;
            --border-focus: #4F46E5;
            --color-text-white: #0F172A;
            --color-text-muted: #64748B;
        }

        [data-theme="light"] body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            background-image:
                radial-gradient(circle at 18% 18%, rgba(79, 70, 229, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 82% 82%, rgba(2, 132, 199, 0.07) 0%, transparent 42%),
                radial-gradient(circle at 50% 10%, rgba(124, 58, 237, 0.05) 0%, transparent 38%);
        }

        [data-theme="light"] .ambient-orb {
            opacity: 0.35;
        }

        [data-theme="light"] .auth-theme-toggle {
            background: rgba(255, 255, 255, 0.92);
            border-color: #CBD5E1;
            color: #334155;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        [data-theme="light"] .auth-theme-toggle:hover {
            background: #FFFFFF;
            border-color: #4F46E5;
            color: #4F46E5;
        }

        [data-theme="light"] .brand-title {
            color: #0F172A;
        }

        [data-theme="light"] .brand-subtitle {
            color: #475569;
        }

        [data-theme="light"] .auth-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #CBD5E1;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        [data-theme="light"] .auth-icon-circle {
            background: rgba(124, 58, 237, 0.08);
            border-color: rgba(124, 58, 237, 0.25);
            color: #7C3AED;
        }

        [data-theme="light"] .auth-title {
            color: #0F172A;
        }

        [data-theme="light"] .auth-description {
            color: #475569;
        }

        [data-theme="light"] .auth-alert {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #B91C1C;
        }

        [data-theme="light"] .auth-alert i {
            color: #DC2626;
        }

        [data-theme="light"] .auth-alert-success {
            background: #F0FDF4;
            border-color: #BBF7D0;
            color: #15803D;
        }

        [data-theme="light"] .auth-alert-success i {
            color: #16A34A;
        }

        [data-theme="light"] .form-label {
            color: #334155;
        }

        [data-theme="light"] .form-control-custom {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: #0F172A;
        }

        [data-theme="light"] .form-control-custom::placeholder {
            color: #94A3B8;
        }

        [data-theme="light"] .form-control-custom:focus {
            background: #FFFFFF;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.16);
        }

        [data-theme="light"] .form-control-otp {
            background: #F8FAFC;
            border-color: rgba(124, 58, 237, 0.35);
            color: #6D28D9;
        }

        [data-theme="light"] .form-control-otp:focus {
            background: #FFFFFF;
            border-color: #7C3AED;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.16);
        }

        [data-theme="light"] .password-hint {
            color: #475569;
        }

        [data-theme="light"] .resend-section {
            border-top-color: #E2E8F0;
        }

        [data-theme="light"] .resend-text {
            color: #475569;
        }

        [data-theme="light"] .btn-resend {
            border-color: rgba(124, 58, 237, 0.35);
            color: #6D28D9;
        }

        [data-theme="light"] .btn-resend:hover:not(:disabled) {
            background: rgba(124, 58, 237, 0.08);
            border-color: #7C3AED;
        }

        [data-theme="light"] .auth-footer-links {
            color: #475569;
        }

        [data-theme="light"] .auth-footer-links a {
            color: #4F46E5;
        }

        [data-theme="light"] .auth-footer {
            color: #64748B;
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

<button type="button" class="auth-theme-toggle" id="authThemeToggleBtn" title="Switch Theme" aria-label="Toggle Light/Dark Mode">
    <i class="bi bi-sun-fill" id="authThemeToggleIcon"></i>
</button>

<div class="ambient-orb ambient-orb-1" aria-hidden="true"></div>
<div class="ambient-orb ambient-orb-2" aria-hidden="true"></div>

<div class="auth-container">

    <!-- Brand -->
    <a href="<?= base_url('login') ?>" class="auth-brand">
        <div class="brand-logo-box">
            <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace Logo" class="brand-logo-crop">
        </div>
        <div class="brand-info">
            <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
            <div class="brand-subtitle">Enterprise Suite</div>
        </div>
    </a>

    <!-- Reset Password Card -->
    <div class="auth-card">

        <div class="auth-header">
            <div class="auth-icon-circle">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h1 class="auth-title">Set New Password</h1>
            <p class="auth-description">
                Enter your recovery code and choose a new password for your account.
            </p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="auth-alert auth-alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= esc($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="auth-alert" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= esc($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors) && is_array($errors)): ?>
            <div class="auth-alert" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>
                    <?php foreach ($errors as $err): ?>
                        <div><?= esc($err) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('reset-password') ?>" method="POST" autocomplete="off" id="resetPasswordForm">
            <?= csrf_field() ?>

            <!-- Email Address -->
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group-custom">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control-custom"
                        placeholder="e.g. nyla@example.com"
                        required
                        value="<?= esc($email ?? old('email') ?? '') ?>"
                        autocomplete="email"
                    >
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            <!-- 6-Digit Recovery Code -->
            <div class="form-group">
                <label for="otp" class="form-label text-center d-block">6-Digit Recovery Code</label>
                <input
                    type="text"
                    id="otp"
                    name="otp"
                    class="form-control-otp"
                    placeholder="······"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    value="<?= esc(old('otp')) ?>"
                >
                <div class="text-center mt-1" style="font-size: 11.5px; color: #64748b;">
                    Code expires in 10 minutes.
                </div>
            </div>

            <!-- New Password -->
            <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-custom"
                        placeholder="Min. 8 characters"
                        required
                        autocomplete="new-password"
                    >
                    <i class="bi bi-lock input-icon"></i>
                </div>
                <div class="password-hint">
                    At least 8 characters, with 1 uppercase letter and 1 number.
                </div>
                <div class="field-error" id="passwordError" role="alert" aria-live="polite"></div>
            </div>

            <!-- Confirm New Password -->
            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm New Password</label>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        class="form-control-custom"
                        placeholder="Re-enter new password"
                        required
                        autocomplete="new-password"
                    >
                    <i class="bi bi-lock-fill input-icon"></i>
                </div>
                <div class="field-error" id="passwordConfirmError" role="alert" aria-live="polite"></div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-primary-auth" id="resetSubmitBtn">
                <i class="bi bi-check-circle"></i>
                Reset Password
            </button>
        </form>

        <!-- Resend Code Section -->
        <div class="resend-section">
            <p class="resend-text">Didn't receive the recovery code?</p>
            <form action="<?= base_url('forgot-password/resend') ?>" method="POST" id="resendResetForm">
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= esc($email ?? old('email') ?? '') ?>" id="resendResetEmailInput">
                <button type="submit" class="btn-resend" id="resendResetBtn">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span id="resendResetBtnText">Resend Code</span>
                </button>
            </form>
            <div id="resetCooldownTimer" class="mt-2" style="font-size: 12px; color: #7b8cae; display: none;"></div>
        </div>

        <div class="auth-footer-links">
            <div>
                Remember your password? <a href="<?= base_url('login') ?>">Back to Sign In</a>
            </div>
        </div>

    </div>

    <div class="auth-footer">
        &copy; <?= date('Y') ?> MeetSpace Enterprise Suite &bull; Secure Room & Resource Management
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
        otpInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            this.value = pasted.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }

    // Keep resend email input synchronized with main email input
    const emailInput = document.getElementById('email');
    const resendEmailInput = document.getElementById('resendResetEmailInput');
    if (emailInput && resendEmailInput) {
        emailInput.addEventListener('input', function() {
            resendEmailInput.value = this.value.trim();
        });
    }

    const resendBtn = document.getElementById('resendResetBtn');
    const resendBtnText = document.getElementById('resendResetBtnText');
    const cooldownTimer = document.getElementById('resetCooldownTimer');
    const cooldownSeconds = <?= (int) ($cooldownRemaining ?? 0) ?>;

    if (cooldownSeconds > 0) {
        startCooldown(cooldownSeconds);
    }

    function startCooldown(seconds) {
        let remaining = seconds;
        resendBtn.disabled = true;
        cooldownTimer.style.display = 'block';

        const interval = setInterval(function() {
            remaining--;
            if (remaining <= 0) {
                clearInterval(interval);
                resendBtn.disabled = false;
                resendBtnText.textContent = 'Resend Code';
                cooldownTimer.style.display = 'none';
            } else {
                resendBtnText.textContent = 'Wait ' + remaining + 's';
                cooldownTimer.textContent = 'You can request another code in ' + remaining + ' seconds.';
            }
        }, 1000);
    }

    const themeBtn = document.getElementById('authThemeToggleBtn');
    const themeIcon = document.getElementById('authThemeToggleIcon');
    const rootEl = document.documentElement;

    function syncThemeIcon() {
        const current = rootEl.getAttribute('data-theme') || 'dark';
        if (themeIcon) {
            themeIcon.className = current === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        }
        if (themeBtn) {
            themeBtn.title = current === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode';
        }
    }

    syncThemeIcon();

    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            const current = rootEl.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            rootEl.classList.add('theme-transitioning');
            rootEl.setAttribute('data-theme', next);
            try {
                localStorage.setItem('meetspace-theme', next);
            } catch (e) {}
            syncThemeIcon();
            setTimeout(function() {
                rootEl.classList.remove('theme-transitioning');
            }, 280);
        });
    }

    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const resetSubmitBtn = document.getElementById('resetSubmitBtn');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const passwordError = document.getElementById('passwordError');
    const passwordConfirmError = document.getElementById('passwordConfirmError');
    let confirmTouched = false;

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
        const val = passwordInput.value;
        if (!val) {
            setFieldError(passwordInput, passwordError, 'New password is required.');
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
        const confirmVal = passwordConfirmInput.value;
        const passVal = passwordInput ? passwordInput.value : '';
        if (!confirmVal) {
            setFieldError(passwordConfirmInput, passwordConfirmError, 'Please confirm your new password.');
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

    if (resetSubmitBtn) {
        resetSubmitBtn.addEventListener('click', function(e) {
            confirmTouched = true;
            const passOk = validatePassword();
            const confirmOk = validatePasswordConfirm();
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

    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            confirmTouched = true;
            const passOk = validatePassword();
            const confirmOk = validatePasswordConfirm();
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
});
</script>

</body>
</html>
