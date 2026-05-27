@extends('laravel-crm::layouts.portal')

@php
    $signedAction = url()->current().'?signature='.request()->input('signature').'&expires='.request()->input('expires');

    if ($purchaseOrder->sent) {
        $statusLabel = ucfirst(__('laravel-crm::lang.sent'));
        $statusClass = 'badge-success';
    } else {
        $statusLabel = ucfirst(__('laravel-crm::lang.pending'));
        $statusClass = 'badge-neutral';
    }
@endphp

@section('content')

    <x-mary-header
        :title="money($purchaseOrder->total, $purchaseOrder->currency).' '.$purchaseOrder->currency"
        :subtitle="ucfirst(__('laravel-crm::lang.purchase_order')).(($purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference) ? ' · '.($purchaseOrder->xeroPurchaseOrder->reference ?? $purchaseOrder->reference) : '')"
    >
        <x-slot:actions>
            <x-mary-badge :value="$statusLabel" class="{{ $statusClass }}" />

            <form action="{{ $signedAction }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="action" value="download" />
                <x-mary-button type="submit" icon="o-arrow-down-tray" class="btn-neutral btn-sm" :label="ucfirst(__('laravel-crm::lang.download'))" />
            </form>
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <div class="grid md:grid-cols-2 gap-x-8 gap-y-2">
            <div>
                <div class="text-sm font-semibold uppercase text-base-content/60 mb-1">
                    {{ ucfirst(__('laravel-crm::lang.supplier')) }}
                </div>
                <div class="text-sm leading-relaxed">
                    @if($purchaseOrder->organization)
                        {{ $purchaseOrder->organization->name }}<br />
                    @endif
                    @isset($purchaseOrder->person)
                        {{ $purchaseOrder->person->name }}<br />
                    @endisset
                    @if(isset($organization_address))
                        @if($organization_address->line1)
                            {{ $organization_address->line1 }}<br />
                        @endif
                        @if($organization_address->line2)
                            {{ $organization_address->line2 }}<br />
                        @endif
                        @if($organization_address->line3)
                            {{ $organization_address->line3 }}<br />
                        @endif
                        @if($organization_address->city || $organization_address->state || $organization_address->postcode)
                            {{ $organization_address->city }} {{ $organization_address->state }} {{ $organization_address->postcode }}<br />
                        @endif
                        {{ $organization_address->country }}
                    @elseif($address)
                        {{ $address->line1 }}<br />
                        @if($address->line2)
                            {{ $address->line2 }}<br />
                        @endif
                        @if($address->line3)
                            {{ $address->line3 }}<br />
                        @endif
                        {{ $address->city }}<br />
                        {{ $address->country }}
                    @endif
                </div>
            </div>

            <div>
                <div class="text-sm font-semibold uppercase text-base-content/60 mb-1">
                    {{ ucfirst(__('laravel-crm::lang.delivery_details')) }}
                </div>
                <div class="text-sm leading-relaxed flex items-start justify-between gap-4">
                    <div>
                        {{ $fromName }}<br />
                        @if($purchaseOrder->delivery_type == 'pickup')
                            {{ strtoupper(__('laravel-crm::lang.pickup')) }}
                        @elseif($purchaseOrder->address)
                            {{ $purchaseOrder->address->line1 }}<br />
                            @if($purchaseOrder->address->line2)
                                {{ $purchaseOrder->address->line2 }}<br />
                            @endif
                            @if($purchaseOrder->address->line3)
                                {{ $purchaseOrder->address->line3 }}<br />
                            @endif
                            {{ $purchaseOrder->address->city }} {{ $purchaseOrder->address->state }} {{ $purchaseOrder->address->postcode }}<br />
                            {{ $purchaseOrder->address->country }}
                            @if($purchaseOrder->address->contact)
                                <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_contact')) }}</strong> {{ $purchaseOrder->address->contact }}
                            @endif
                            @if($purchaseOrder->address->phone)
                                <br /><strong>{{ ucfirst(__('laravel-crm::lang.delivery_phone')) }}</strong> {{ $purchaseOrder->address->phone }}
                            @endif
                        @endif
                    </div>
                    @if($logo)
                        <img src="{{ asset('storage/'.$logo) }}" class="max-h-24" alt="" />
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <dl class="grid md:grid-cols-2 gap-x-8 gap-y-1 text-sm pt-2 border-t border-base-content/10">
                    @if($purchaseOrder->purchase_order_id)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.purchase_order_number')) }}</dt>
                            <dd>{{ $purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id }}</dd>
                        </div>
                    @endif
                    @if($purchaseOrder->issue_date)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.purchase_order_date')) }}</dt>
                            <dd>{{ $purchaseOrder->issue_date->format($dateFormat) }}</dd>
                        </div>
                    @endif
                    @if($purchaseOrder->delivery_date)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.delivery_date')) }}</dt>
                            <dd>{{ $purchaseOrder->delivery_date->format($dateFormat) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="pt-4 mt-4 border-t border-base-content/10">
            @livewire('crm-portal-purchase-order-line-items', ['purchaseOrder' => $purchaseOrder, 'taxName' => $taxName])
        </div>

        <div class="pt-4 mt-2 flex justify-end">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm min-w-[16rem]">
                <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</dt>
                <dd class="text-right">{{ money($purchaseOrder->subtotal, $purchaseOrder->currency) }}</dd>

                @if($purchaseOrder->discount > 0)
                    <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.discount')) }}</dt>
                    <dd class="text-right">{{ money($purchaseOrder->discount, $purchaseOrder->currency) }}</dd>
                @endif

                <dt class="font-semibold text-right">{{ $taxName }}</dt>
                <dd class="text-right">{{ money($purchaseOrder->tax, $purchaseOrder->currency) }}</dd>

                <dt class="font-semibold text-right border-t border-base-content/20 pt-1">{{ ucfirst(__('laravel-crm::lang.total')) }}</dt>
                <dd class="text-right border-t border-base-content/20 pt-1">{{ money($purchaseOrder->total, $purchaseOrder->currency) }}</dd>
            </dl>
        </div>

        @if($purchaseOrder->terms)
            <div class="pt-4 mt-4 border-t border-base-content/10">
                <h5 class="font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.terms')) }}</h5>
                <div class="text-sm">{!! nl2br(e($purchaseOrder->terms)) !!}</div>
            </div>
        @endif
    </x-mary-card>

@endsection
