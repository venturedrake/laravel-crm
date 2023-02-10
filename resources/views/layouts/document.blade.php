<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>{{ (config('app.name')) ? config('app.name').' - ' : null }} CRM - Document</title>
    
    <!-- Styles -->
    <link href="{{ asset('vendor/laravel-crm/css/document.css') }}" rel="stylesheet">

    <style>
        @page {
            @bottom-right {
                content: counter(page) " of " counter(pages);
            }
        }

        .page-break {
            page-break-after: always;
        }
        
        .container-document{
            width:18.6cm
        }
    </style>
</head>
<body>
    <div class="container-document">
       @yield('content', $slot ?? null)
    </div>
</body>
</html>