<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sign In' ?> - MeetSpace Enterprise Suite</title>

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

        .forgot-password-link {
            color: #38bdf8;
            font-size: 12.5px;
            font-weight: 500;
            text-decoration: none;
        }

        .forgot-password-link:hover {
            text-decoration: underline;
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

        [data-theme="light"] .login-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #E2E8F0;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        [data-theme="light"] .login-title {
            color: #0F172A;
        }

        [data-theme="light"] .login-description {
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

        [data-theme="light"] .form-control-custom:focus {
            background: #FFFFFF;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.16);
        }

        [data-theme="light"] .form-control-custom:focus + .input-icon,
        [data-theme="light"] .input-group-custom:focus-within .input-icon {
            color: #4F46E5;
        }

        [data-theme="light"] .login-alert {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #991B1B;
        }

        [data-theme="light"] .login-alert i {
            color: #DC2626;
        }

        [data-theme="light"] .login-alert-success {
            background: #ECFDF5;
            border-color: #A7F3D0;
            color: #065F46;
        }

        [data-theme="light"] .login-alert-success i {
            color: #059669;
        }

        [data-theme="light"] .login-footer-links {
            color: #475569;
        }

        [data-theme="light"] .login-footer-links a,
        [data-theme="light"] .forgot-password-link {
            color: #4F46E5;
        }

        [data-theme="light"] .login-footer {
            color: #64748B;
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
        html.theme-transitioning .login-card,
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
    </style>
</head>
<body>

<button type="button" class="auth-theme-toggle" id="authThemeToggleBtn" aria-label="Toggle theme mode" title="Switch Theme">
    <i class="bi bi-moon-stars-fill" id="authThemeIcon"></i>
    <span id="authThemeLabel">Dark</span>
</button>

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

        <form action="<?= base_url('login') ?>" method="POST" autocomplete="on">
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
                    >
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="<?= base_url('forgot-password') ?>" class="forgot-password-link">Forgot password?</a>
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
                    >
                    <i class="bi bi-lock input-icon"></i>
                </div>
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
})();
</script>

</body>
</html>
