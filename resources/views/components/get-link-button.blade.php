@php
    // Wrapped in a `payload` key because Livewire matches a JS dispatch's
    // object keys to the listener's parameter names — this is the single
    // `array $payload` argument GetLink::open() takes.
    //
    // One escaped JSON literal rather than five interpolated strings: titles
    // carry apostrophes ("O'Brien Ltd") that would otherwise break out of the
    // Alpine expression.
    $payload = \Illuminate\Support\Js::from([
        'payload' => [
            'url' => $url,
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'canMarkSent' => $canMarkSent,
        ],
    ]);

    // Escaped separately rather than read off $payload: an Alpine expression
    // has to stay a single expression (no temp variable), because Alpine
    // compiles it as the right-hand side of an assignment.
    $portalUrl = \Illuminate\Support\Js::from($url);
@endphp

{{-- window.Livewire.dispatch, not $dispatch: a plain Alpine $dispatch only
     bubbles up through this button's own Livewire root, and the modal is a
     sibling mounted in the layout — the event would never reach it. x-data is
     still required to give the click handler an Alpine scope to evaluate in.

     The `crmGetLinkMounted` guard covers the host that published
     layouts/app.blade.php before this feature shipped: their layout has no
     <livewire:crm-get-link /> in it, so the dispatch would go nowhere and the
     button would be silently dead. Opening the portal page itself is the next
     best thing — the link is at least copyable out of the address bar. --}}
<x-mary-button
    x-data
    icon="o-link"
    tooltip="{{ ucfirst(__('laravel-crm::lang.get_link')) }}"
    class="btn-sm btn-square btn-outline"
    @click="window.crmGetLinkMounted ? window.Livewire.dispatch('crm-get-link', {{ $payload }}) : window.open({{ $portalUrl }}, '_blank', 'noopener')"
/>
