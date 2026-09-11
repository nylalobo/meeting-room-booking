<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Email Verification' ?> - MeetSpace Enterprise Suite</title>

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

        .status-container {
            width: 100%;
            max-width: 440px;
        }

        .status-brand {
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

        .status-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
        }

        .status-icon-box {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
        }

        .status-icon-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        .status-icon-expired {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }

        .status-icon-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .status-title {
            font-size: 21px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
        }

        .status-message {
            font-size: 14px;
            color: var(--color-text-muted);
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14.5px;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            transition: all 0.15s ease;
        }

        .btn-action:hover {
            background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary-action {
            display: inline-block;
            margin-top: 14px;
            font-size: 13px;
            color: #94a3b8;
            text-decoration: none;
        }

        .btn-secondary-action:hover {
            color: #38bdf8;
            text-decoration: underline;
        }

        .status-footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="status-container">

    <!-- MeetSpace Branding -->
    <div class="status-brand">
        <div class="brand-logo-box">
            <img src="<?= base_url('assets/images/meetspace-logo.png') ?>" alt="MeetSpace Logo" class="brand-logo-crop">
        </div>
        <div class="brand-info">
            <div class="brand-title">Meet<span class="brand-title-accent">Space</span></div>
            <div class="brand-subtitle">Enterprise Suite</div>
        </div>
    </div>

    <!-- Status Card -->
    <div class="status-card">

        <?php if (($status ?? '') === 'success'): ?>
            <div class="status-icon-box status-icon-success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h1 class="status-title">Email Verified!</h1>
            <p class="status-message">
                <?= esc($message ?? 'Your email address has been successfully verified. Your MeetSpace account is now active and ready to use.') ?>
            </p>
            <a href="<?= base_url('login') ?>" class="btn-action">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign In to Your Account
            </a>

        <?php elseif (($status ?? '') === 'expired'): ?>
            <div class="status-icon-box status-icon-expired">
                <i class="bi bi-hourglass-bottom"></i>
            </div>
            <h1 class="status-title">Link Expired</h1>
            <p class="status-message">
                <?= esc($message ?? 'This verification link has expired. Verification links are valid for 24 hours from issuance.') ?>
            </p>
            <a href="<?= base_url('resend-verification') ?>" class="btn-action">
                <i class="bi bi-send-fill"></i>
                Request New Verification Link
            </a>
            <div>
                <a href="<?= base_url('login') ?>" class="btn-secondary-action">Return to Sign In</a>
            </div>

        <?php else: ?>
            <div class="status-icon-box status-icon-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h1 class="status-title">Invalid Link</h1>
            <p class="status-message">
                <?= esc($message ?? 'This verification link is invalid, broken, or has already been used.') ?>
            </p>
            <a href="<?= base_url('resend-verification') ?>" class="btn-action">
                <i class="bi bi-send-fill"></i>
                Resend Verification Email
            </a>
            <div>
                <a href="<?= base_url('login') ?>" class="btn-secondary-action">Return to Sign In</a>
            </div>
        <?php endif; ?>

    </div>

    <div class="status-footer">
        &copy; <?= date('Y') ?> MeetSpace Enterprise Suite &bull; Secure Room & Resource Management
    </div>

</div>

</body>
</html>
