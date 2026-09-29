<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sign In' ?> - MeetSpace Enterprise Suite</title>

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

        .login-container {
            width: 100%;
            max-width: 420px;
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

        .login-brand {
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

        .login-card {
            background: rgba(17, 26, 46, 0.72);
            border: 1px solid var(--border-glass, rgba(129, 140, 248, 0.16));
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.55), 0 0 24px rgba(99, 102, 241, 0.08);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .login-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .login-title {
            font-size: 19px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .login-description {
            font-size: 13.5px;
            color: var(--color-text-muted);
            line-height: 1.4;
        }

        .login-alert {
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

        .login-alert i {
            font-size: 16px;
            color: #ef4444;
            flex-shrink: 0;
        }

        .login-alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.28);
            color: #86efac;
        }

        .login-alert-success i {
            color: #22c55e;
        }

        .login-footer-links {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        .login-footer-links a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer-links a.register-link {
            font-weight: 600;
        }

        .login-footer-links a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
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

        .btn-signin {
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

        .btn-signin:hover {
            background: linear-gradient(135deg, #4F46E5 0%, #6D28D9 100%);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5), 0 0 12px rgba(56, 189, 248, 0.25);
            transform: translateY(-2px);
            color: #FFFFFF;
            text-decoration: none;
        }

        .btn-signin:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .login-footer {
            margin-top: 28px;
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

<div class="login-container">

    <!-- MeetSpace Branding -->
    <div class="login-brand">
        <div class="brand-logo-box">
            <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace Logo" class="brand-logo-crop">
        </div>
        <div class="brand-info">
            <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
            <div class="brand-subtitle">Enterprise Suite</div>
        </div>
    </div>

    <!-- Login Card -->
    <div class="login-card">

        <div class="login-header">
            <h1 class="login-title">Sign In</h1>
            <p class="login-description">Enter your enterprise credentials to access your workspace.</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="login-alert login-alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= esc($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="login-alert" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= esc($error) ?></span>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="<?= base_url('login') ?>" method="POST" autocomplete="on" novalidate>
            <?= csrf_field() ?>
            <?php if (!empty($returnUrl)): ?>
                <input type="hidden" name="return_url" value="<?= esc($returnUrl) ?>">
            <?php endif; ?>

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
                        autofocus
                        value="<?= esc(old('email')) ?>"
                        autocomplete="email"
                        aria-describedby="email-error"
                    >
                    <i class="bi bi-envelope input-icon"></i>
                </div>
                <div id="email-error" class="invalid-feedback-custom" role="alert"></div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="<?= base_url('forgot-password') ?>" style="color: #38bdf8; font-size: 12.5px; text-decoration: none;">Forgot password?</a>
                </div>
                <div class="input-group-custom">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-custom"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                        aria-describedby="password-error"
                    >
                    <i class="bi bi-lock input-icon"></i>
                </div>
                <div id="password-error" class="invalid-feedback-custom" role="alert"></div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-signin" id="submitLoginBtn">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign In
            </button>
        </form>

        <div class="login-footer-links">
            <div>
                <a href="<?= base_url('verify-email') ?>">Need to verify your email? Enter code</a>
            </div>
            <div style="margin-top: 8px;">
                Don't have an account? <a href="<?= base_url('register') ?>" class="register-link">Create Account</a>
            </div>
        </div>

    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> MeetSpace Enterprise Suite &bull; Secure Room & Resource Management
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');

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

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                const val = this.value.trim();
                if (val !== '' && isValidEmail(val)) {
                    clearFieldError(this, emailError);
                }
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                if (this.value.length > 0) {
                    clearFieldError(this, passwordError);
                }
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let hasError = false;
            let firstInvalid = null;

            // Clear previous frontend error messages
            clearFieldError(emailInput, emailError);
            clearFieldError(passwordInput, passwordError);

            // Validate Email
            const emailVal = emailInput ? emailInput.value.trim() : '';
            if (!emailVal) {
                setFieldError(emailInput, emailError, 'Email address is required.');
                hasError = true;
                if (!firstInvalid) firstInvalid = emailInput;
            } else if (!isValidEmail(emailVal)) {
                setFieldError(emailInput, emailError, 'Please provide a valid email address.');
                hasError = true;
                if (!firstInvalid) firstInvalid = emailInput;
            }

            // Validate Password
            const passwordVal = passwordInput ? passwordInput.value : '';
            if (!passwordVal) {
                setFieldError(passwordInput, passwordError, 'Password is required.');
                hasError = true;
                if (!firstInvalid) firstInvalid = passwordInput;
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
