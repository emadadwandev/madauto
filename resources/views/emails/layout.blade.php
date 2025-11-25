<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notification' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
        .footer-logo-text {
            font-weight: bold;
            color: #555;
            font-size: 14px;
            margin-top: 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3490dc;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .content {
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embedData(file_get_contents(public_path('images/main_logo.png')), 'logo.png', 'image/png') }}" alt="Logo" class="logo">
            <div class="footer-logo-text">MAD Automation</div>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} MAD Automation. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
