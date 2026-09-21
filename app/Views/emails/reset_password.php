<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your MeetSpace password</title>
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
            max-width: 540px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        .header {
            padding: 32px 40px 20px;
            text-align: center;
            background: linear-gradient(180deg, #0d1330 0%, #0f1535 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .brand-title {
            font-size: 22px;
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
            padding: 32px 40px;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 14px;
        }
        .otp-container {
            text-align: center;
            margin: 28px 0;
        }
        .otp-box {
            display: inline-block;
            background: #090e2b;
            border: 2px solid #a855f7;
            border-radius: 10px;
            padding: 16px 32px;
            letter-spacing: 10px;
            font-size: 32px;
            font-weight: 800;
            color: #c084fc;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.2);
        }
        .expiry-notice {
            text-align: center;
            font-size: 13.5px;
            color: #94a3b8;
            margin-top: -12px;
            margin-bottom: 24px;
        }
        .notice {
            margin-top: 24px;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 20px;
        }
        .footer {
            padding: 20px 40px 28px;
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
                    We received a request to reset your password for <strong>MeetSpace Enterprise Suite</strong>. Enter the 6-digit recovery code below to proceed with setting a new password:
                </p>

                <div class="otp-container">
                    <div class="otp-box"><?= esc($otp) ?></div>
                </div>

                <div class="expiry-notice">
                    This recovery code expires in <strong>10 minutes</strong>.
                </div>

                <div class="notice">
                    If you did not request a password reset, you can safely ignore this email. Your current password will remain active and unchanged.
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
