<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ ucfirst(__('laravel-crm::lang.unsubscribed_success')) }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background:#f5f5f5; margin:0; padding:48px 16px; color:#222; }
        .card { max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,0.1); text-align:center; }
        h1 { font-size:20px; margin:0 0 16px; color:#16a34a; }
        p { color:#555; line-height:1.5; }
        .number { font-weight:600; color:#222; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ ucfirst(__('laravel-crm::lang.unsubscribed_success')) }}</h1>
        <p>{{ __('laravel-crm::lang.sms_unsubscribed_message') }}</p>
        <p class="number">{{ $recipient->phone?->number }}</p>
    </div>
</body>
</html>
