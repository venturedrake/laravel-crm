{{-- TABS (DaisyUI tabs-lift + tab content — same shape as Settings → Templates / General).

     Unlike those two, each integration is its own full-page Livewire route rather than a
     panel of one component, so the tabs are links instead of radios. DaisyUI reveals a
     panel with `active-tab + .tab-content`, so the panel has to be emitted immediately
     after the active tab, inside the tablist — hence the slot. --}}
@php
    $activeIntegrationTab = request()->routeIs('laravel-crm.integrations.clicksend*') ? 'clicksend' : 'xero';

    $integrationTabs = [
        ['key' => 'xero', 'label' => 'Xero', 'url' => route('laravel-crm.integrations.xero')],
    ];

    if (\VentureDrake\LaravelCrm\Support\Modules::enabled('sms-marketing')) {
        $integrationTabs[] = ['key' => 'clicksend', 'label' => 'ClickSend', 'url' => route('laravel-crm.integrations.clicksend')];
    }
@endphp

<div role="tablist" class="tabs tabs-lift">
    @foreach ($integrationTabs as $integrationTab)
        <a role="tab"
           href="{{ $integrationTab['url'] }}"
           class="tab {{ $activeIntegrationTab === $integrationTab['key'] ? 'tab-active' : '' }}"
           @if ($activeIntegrationTab === $integrationTab['key']) aria-current="page" @endif>{{ $integrationTab['label'] }}</a>

        @if ($activeIntegrationTab === $integrationTab['key'])
            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6">
                {{ $slot }}
            </div>
        @endif
    @endforeach
</div>
