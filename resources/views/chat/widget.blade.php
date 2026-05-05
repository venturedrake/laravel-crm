<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat</title>
    {{ \Illuminate\Support\Facades\Vite::useBuildDirectory('vendor/laravel-crm')->withEntryPoints(['resources/css/app.css', 'resources/js/app.js']) }}
    @livewireStyles
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #fff; }
    </style>
</head>
<body>
    <livewire:crm-chat-widget-panel :public-key="$widget->public_key" :visitor-token="$visitorToken" />
    @livewireScripts
</body>
</html>

