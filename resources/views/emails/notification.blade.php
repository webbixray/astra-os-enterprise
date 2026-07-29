<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notification' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a2e;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 24px;
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #ffffff;
            padding: 24px 32px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .content {
            background: #ffffff;
            padding: 32px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .priority-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .priority-low {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .priority-normal {
            background: #e3f2fd;
            color: #1565c0;
        }
        .priority-high {
            background: #fff3e0;
            color: #e65100;
        }
        .priority-urgent {
            background: #fce4ec;
            color: #c62828;
        }
        .footer {
            text-align: center;
            padding: 16px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $title ?? 'Astra OS Notification' }}</h1>
        </div>
        <div class="content">
            @if(isset($priority) && $priority !== 'normal')
                <div class="priority-badge priority-{{ $priority }}">
                    {{ $priority }}
                </div>
            @endif

            <p>{{ $body ?? '' }}</p>

            @if(! empty($data))
                <hr style="border: none; border-top: 1px solid #eee; margin: 16px 0;">
                <details>
                    <summary style="cursor: pointer; font-size: 14px; color: #666;">
                        Additional Details
                    </summary>
                    <pre style="background: #f5f5f5; padding: 12px; border-radius: 4px; font-size: 12px; overflow-x: auto; margin-top: 8px;">
                        {{ json_encode($data, JSON_PRETTY_PRINT) }}
                    </pre>
                </details>
            @endif
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Astra OS Enterprise. All rights reserved.
        </div>
    </div>
</body>
</html>
