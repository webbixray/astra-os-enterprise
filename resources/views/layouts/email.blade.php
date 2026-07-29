<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f5; color: #18181b; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 32px 24px; text-align: center; border-radius: 12px 12px 0 0; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; }
        .header p { color: #c4b5fd; margin: 8px 0 0; font-size: 14px; }
        .content { background: #ffffff; padding: 32px 24px; border-radius: 0 0 12px 12px; }
        .button { display: inline-block; background: #6366f1; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; }
        .button:hover { background: #4f46e5; }
        .footer { text-align: center; padding: 24px; color: #71717a; font-size: 12px; }
        .detail { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e4e4e7; }
        .detail-label { color: #71717a; font-size: 14px; }
        .detail-value { color: #18181b; font-size: 14px; font-weight: 500; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
        <div class="footer">
            <p>Astra OS Enterprise &mdash; AI-Native Marketing Platform</p>
            <p>&copy; {{ date('Y') }} Astra OS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
