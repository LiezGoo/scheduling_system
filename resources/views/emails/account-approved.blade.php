<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved - SorSU Scheduling System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #660000 0%, #8B0000 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            line-height: 1.8;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            margin: 20px 0;
            font-size: 15px;
            color: #555;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #660000 0%, #8B0000 100%);
            color: white !important;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 25px 0;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(102, 0, 0, 0.3);
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #8B0000 0%, #660000 100%);
            box-shadow: 0 6px 16px rgba(102, 0, 0, 0.4);
        }
        .footer-note {
            font-size: 14px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .signature {
            margin-top: 30px;
            font-size: 15px;
            color: #555;
        }
        .footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="SorSU Logo">
            <h1>Account Approved</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Dear {{ $user->first_name }},</p>

            <p class="message">
                Your registration for the SorSU Scheduling System has been approved.
            </p>

            <p class="message">
                You may now log in using your registered university email.
            </p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/login" class="cta-button">Login Here</a>
            </div>

            <p class="footer-note">
                If you did not request this account, please contact the system administrator.
            </p>

            <div class="signature">
                <p>Best regards,<br>
                <strong>SorSU Scheduling System</strong></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} Sorsogon State University. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
