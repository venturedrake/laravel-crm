@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')]){{ config('app.name') }}@endcomponent
@endslot

{{-- Body --}}
<h2 style="margin-top:0;">{{ ucfirst(__('laravel-crm::lang.welcome_email_heading', ['app' => config('app.name')])) }}</h2>

<p>{{ ucfirst(__('laravel-crm::lang.welcome_email_greeting', ['name' => $name])) }}</p>

<p>{{ ucfirst(__('laravel-crm::lang.welcome_email_body', ['app' => config('app.name')])) }}</p>

@component('mail::button', ['url' => $setPasswordUrl, 'color' => 'primary'])
{{ ucfirst(__('laravel-crm::lang.welcome_email_cta')) }}
@endcomponent

<p style="font-size:0.875em;color:#6b7280;">
    {{ ucfirst(__('laravel-crm::lang.welcome_email_link_expiry')) }}
</p>

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<p>Powered by <a href="https://laravelcrm.com">Laravel CRM</a></p>
{{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent

