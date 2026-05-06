<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ ucfirst(__('laravel-crm::lang.unsubscribe_confirm')) }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background:#f5f5f5; margin:0; padding:48px 16px; color:#222; }
        .card { max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,0.1); text-align:center; }
        h1 { font-size:20px; margin:0 0 16px; }
        p { color:#555; line-height:1.5; }
        button { background:#dc2626; color:#fff; border:0; padding:12px 24px; border-radius:6px; font-size:14px; cursor:pointer; margin-top:16px; }
        button:hover { background:#b91c1c; }
        .number { font-weight:600; color:#222; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ ucfirst(__('laravel-crm::lang.unsubscribe_confirm')) }}</h1>
        <p>{{ __('laravel-crm::lang.sms_unsubscribe_prompt') }}</p>
        <p class="number">{{ $recipient->phone?->number }}</p>
        <form method="POST" action="{{ route('laravel-crm.sms-tracking.unsubscribe.confirm', ['token' => $token]) }}">
            @csrf
            <button type="submit">{{ ucfirst(__('laravel-crm::lang.unsubscribe')) }}</button>
        </form>
    </div>
</body>
</html>
