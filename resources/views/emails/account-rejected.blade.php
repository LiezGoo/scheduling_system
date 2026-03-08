<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Update - SorSU Scheduling System</title>
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
            background: linear-gradient(135deg, #8B0000 0%, #660000 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header img {
            max-width: 120px;
            height: auto;
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
        .reason-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 18px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .reason-label {
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .reason-text {
            color: #856404;
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
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
            <img src="{{ url('images/logo.png') }}" alt="SorSU Logo" width="120">
            <h1>Registration Update</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Dear {{ $user->first_name }},</p>

            <p class="message">
                We regret to inform you that your registration request for the SorSU Scheduling System has been declined.
            </p>

            @if($reason && trim($reason) !== '')
            <div class="reason-box">
                <div class="reason-label">Reason:</div>
                <p class="reason-text">{{ $reason }}</p>
            </div>
            @endif

            <p class="footer-note">
                If you believe this was a mistake, please contact the administrator.
            </p>

            <div class="signature">
                <p>Thank you.<br><br>
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
