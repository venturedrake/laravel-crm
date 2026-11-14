<!DOCTYPE html>
<html><head><meta name="csrf-token" content="{{ csrf_token() }}">
@livewireStyles
{{ \Illuminate\Support\Facades\Vite::useBuildDirectory('vendor/laravel-crm')->withEntryPoints(['resources/css/app.css', 'resources/js/app.js']) }}
</head><body>
@livewire($component, $parameters)
@livewireScripts
</body></html>
