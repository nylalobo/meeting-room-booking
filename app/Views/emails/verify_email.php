<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your MeetSpace account</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #070b28;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #ffffff;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-spacing: 0;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #070b28;
            padding-bottom: 40px;
        }
        .main-table {
            background-color: #0f1535;
            margin: 0 auto;
            width: 100%;
            max-width: 580px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        .header {
            padding: 36px 40px 24px;
            text-align: center;
            background: linear-gradient(180deg, #0d1330 0%, #0f1535 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .brand-title {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .brand-accent {
            color: #38bdf8;
        }
        .brand-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .content {
            padding: 36px 40px;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 16px;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn-verify {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
        .link-fallback {
            margin-top: 24px;
            padding: 16px;
            background: #090e2b;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            word-break: break-all;
            font-size: 12.5px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .link-fallback a {
            color: #38bdf8;
            text-decoration: underline;
        }
        .notice {
            margin-top: 24px;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 20px;
        }
        .footer {
            padding: 24px 40px 32px;
            text-align: center;
            font-size: 12px;
            color: #475569;
        }
    </style>
</head>
<body>

<center class="wrapper">
    <table class="main-table" width="100%">
        <!-- Header -->
        <tr>
            <td class="header">
                <div class="brand-title">Meet<span class="brand-accent">Space</span></div>
                <div class="brand-subtitle">Enterprise Suite</div>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td class="content">
                <div class="greeting">Hello <?= esc($firstName) ?>,</div>

                <p style="margin-top: 0;">
                    Thank you for creating an account with <strong>MeetSpace Enterprise Suite</strong>. To activate your account and gain access to the meeting room booking system, please verify your email address.
                </p>

                <div class="btn-container">
                    <a href="<?= esc($verificationUrl) ?>" class="btn-verify" target="_blank">
                        Verify Email Address
                    </a>
                </div>

                <p style="font-size: 13.5px; color: #94a3b8;">
                    This verification link is valid for <strong><?= esc($expiresHours ?? 24) ?> hours</strong>.
                </p>

                <div class="link-fallback">
                    If the button above does not work, copy and paste the following URL into your web browser:<br>
                    <a href="<?= esc($verificationUrl) ?>"><?= esc($verificationUrl) ?></a>
                </div>

                <div class="notice">
                    If you did not create an account with MeetSpace Enterprise Suite, no further action is required; you can safely ignore this email.
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td class="footer">
                &copy; <?= date('Y') ?> MeetSpace Enterprise Suite &bull; Secure Room & Resource Management
            </td>
        </tr>
    </table>
</center>

</body>
</html>
