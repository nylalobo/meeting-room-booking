<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Resend Verification' ?> - MeetSpace Enterprise Suite</title>

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

        .resend-container {
            width: 100%;
            max-width: 420px;
        }

        .resend-brand {
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
            letter-spacing: -0.01em;
        }

        .brand-title-accent {
            background: linear-gradient(135deg, #38bdf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #38bdf8;
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

        .resend-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 36px 32px;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
        }

        .resend-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .resend-title {
            font-size: 19px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .resend-description {
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

        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.28);
            color: #86efac;
        }

        .alert-success i {
            font-size: 16px;
            color: #22c55e;
            flex-shrink: 0;
            margin-top: 2px;
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
            background: #090e2b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            color: #ffffff;
            font-size: 14px;
            padding: 11px 16px 11px 40px;
            outline: none;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .form-control-custom:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
            background: #070c24;
        }

        .form-control-custom:focus + .input-icon,
        .input-group-custom:focus-within .input-icon {
            color: #38bdf8;
        }

        .form-control-custom::placeholder {
            color: #475569;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
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
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            margin-top: 24px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 100%);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .link-options {
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }

        .link-options a {
            color: #38bdf8;
            text-decoration: none;
        }

        .link-options a:hover {
            text-decoration: underline;
        }

        .resend-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="resend-container">

    <!-- MeetSpace Branding -->
    <div class="resend-brand">
        <div class="brand-logo-box">
            <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace Logo" class="brand-logo-crop">
        </div>
        <div class="brand-info">
            <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
            <div class="brand-subtitle">Enterprise Suite</div>
        </div>
    </div>

    <!-- Resend Card -->
    <div class="resend-card">

        <div class="resend-header">
            <h1 class="resend-title">Resend Verification</h1>
            <p class="resend-description">Enter your registered email address to receive a fresh verification link.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-error" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= esc($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-custom alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= esc($success) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('resend-verification') ?>" method="POST" autocomplete="on">
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
                        placeholder="john.doe@enterprise.com"
                        required
                        autofocus
                        value="<?= esc(old('email')) ?>"
                        autocomplete="email"
                    >
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-submit" id="submitResendBtn">
                <i class="bi bi-send-fill"></i>
                Send Verification Link
            </button>
        </form>

        <div class="link-options">
            <a href="<?= base_url('login') ?>">Back to Sign In</a>
            <a href="<?= base_url('register') ?>">Create Account</a>
        </div>

    </div>

    <div class="resend-footer">
        &copy; <?= date('Y') ?> MeetSpace Enterprise Suite &bull; Secure Room & Resource Management
    </div>

</div>

</body>
</html>
