<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>{{ (config('app.name')) ? config('app.name').' - ' : null }} CRM - Document</title>
    
    <!-- Styles -->
    <link href="{{ asset('vendor/laravel-crm/css/document.css') }}?c=57898086907890" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Nunito';
            font-style: normal;
            font-weight: normal;
            src: url('vendor/laravel-crm/fonts/Nunito-Regular.ttf') format('truetype');
        }
        
        @font-face {
            font-family: 'Nunito';
            font-style: normal;
            font-weight: 500;
            src: url('vendor/laravel-crm/fonts/Nunito-Medium.ttf') format('truetype');
        }

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