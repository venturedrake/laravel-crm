@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')]){{ config('app.name') }}@endcomponent
@endslot

{{-- Body --}}
<h2 style="margin-top:0;">💬 Missed Chat Message</h2>

<p>You received a chat message while no agents were online.</p>

@component('mail::panel')
<strong>Message:</strong><br>
{{ $message->body }}
@endcomponent

@component('mail::table')
| | |
|:--|:--|
| **Name** | {{ $visitor?->name ?: '—' }} |
| **Email** | {{ $visitor?->email ?: '—' }} |
| **Conversation** | {{ $conversation->chat_id }} |
| **Sent** | {{ $message->created_at->format('d M Y, H:i') }} |
@endcomponent

@component('mail::button', ['url' => url(route('laravel-crm.chat.show', $conversation))])
View Conversation
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<p>Powered by <a href="https://laravelcrm.com">Laravel CRM</a></p>
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent

