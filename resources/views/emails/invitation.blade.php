<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            margin: 0 0 20px;
            color: #555;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 25px 0;
        }
        .info-box p {
            margin: 5px 0;
        }
        .info-box strong {
            color: #333;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin: 25px 0;
            font-size: 14px;
        }
        .footer {
            background: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6c757d;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Team Invitation</h1>
        </div>

        <div class="content">
            <p>Hello!</p>

            <p><strong>{{ $invitation->invitedBy->name }}</strong> has invited you to join <strong>{{ $invitation->tenant->name }}</strong> as a <strong>{{ $invitation->role->display_name }}</strong>.</p>

            <div class="info-box">
                <p><strong>Organization:</strong> {{ $invitation->tenant->name }}</p>
                <p><strong>Role:</strong> {{ $invitation->role->display_name }}</p>
                <p><strong>Invited by:</strong> {{ $invitation->invitedBy->name }} ({{ $invitation->invitedBy->email }})</p>
            </div>

            <p>Click the button below to accept this invitation and create your account:</p>

            <div class="button-container">
                <a href="{{ $acceptUrl }}" class="button">Accept Invitation</a>
            </div>

            <div class="note">
                <strong>Note:</strong> This invitation will expire {{ $expiresAt }}. If you don't accept it before then, you'll need to request a new invitation.
            </div>

            <p>If you weren't expecting this invitation or have any questions, please contact {{ $invitation->invitedBy->name }} at {{ $invitation->invitedBy->email }}.</p>

            <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
                If the button above doesn't work, copy and paste this link into your browser:<br>
                <a href="{{ $acceptUrl }}" style="color: #667eea; word-break: break-all;">{{ $acceptUrl }}</a>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
