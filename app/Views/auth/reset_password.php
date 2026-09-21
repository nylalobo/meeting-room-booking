<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Reset Password' ?> - MeetSpace Enterprise Suite</title>

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
            --bg-main: #070b28;
            --bg-card: #0f1535;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-focus: #38bdf8;
            --color-text-white: #ffffff;
            --color-text-muted: #7b8cae;
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-main);
            color: var(--color-text-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image:
                radial-gradient(circle at 15% 15%, rgba(56, 189, 248, 0.07) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(168, 85, 247, 0.07) 0%, transparent 40%);
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
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
            background: #040921;
            border-radius: 11px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(56, 189, 248, 0.28);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 16px rgba(56, 189, 248, 0.15);
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
            color: #38bdf8;
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
            background-color: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(56, 189, 248, 0.05);
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
            background: #090e2b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            color: #ffffff;
            font-size: 14px;
            padding: 10px 16px 10px 40px;
            outline: none;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .form-control-custom:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
            background: #070c24;
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
            background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
            margin-top: 24px;
        }

        .btn-primary-auth:hover {
            background: linear-gradient(135deg, #6d28d9 0%, #1d4ed8 100%);
            box-shadow: 0 6px 18px rgba(124, 58, 237, 0.4);
            transform: translateY(-1px);
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
    </style>
</head>
<body>

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
});
</script>

</body>
</html>
