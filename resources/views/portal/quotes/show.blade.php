@extends('laravel-crm::layouts.portal')

@php
    $signedAction = url()->current().'?signature='.request()->input('signature').'&expires='.request()->input('expires');
    $isAccepted = (bool) $quote->accepted_at;
    $isRejected = (bool) $quote->rejected_at;
    $isExpired = $quote->expire_at && \Carbon\Carbon::now() > $quote->expire_at;
    $isPending = ! $isAccepted && ! $isRejected && ! $isExpired;

    if ($isAccepted) {
        $statusLabel = ucfirst(__('laravel-crm::lang.accepted'));
        $statusClass = 'badge-success';
    } elseif ($isRejected) {
        $statusLabel = ucfirst(__('laravel-crm::lang.rejected'));
        $statusClass = 'badge-error';
    } elseif ($isExpired) {
        $statusLabel = ucfirst(__('laravel-crm::lang.quote_expired'));
        $statusClass = 'badge-error';
    } elseif ($quote->expire_at) {
        $statusLabel = ucfirst(__('laravel-crm::lang.expires_in')).' '.$quote->expire_at->diffForHumans();
        $statusClass = 'badge-neutral';
    } else {
        $statusLabel = ucfirst(__('laravel-crm::lang.pending'));
        $statusClass = 'badge-neutral';
    }

@endphp

@section('content')

    <x-mary-header
        :title="money($quote->total, $quote->currency).' '.$quote->currency"
        :subtitle="ucfirst(__('laravel-crm::lang.quote')).($quote->reference ? ' · '.$quote->reference : '')"
    >
        <x-slot:actions>
            <x-mary-badge :value="$statusLabel" class="{{ $statusClass }}" />

            @if($isPending)
                <form action="{{ $signedAction }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="accept" />
                    <x-mary-button type="submit" class="btn-success btn-sm" :label="ucfirst(__('laravel-crm::lang.accept'))" />
                </form>

                <form action="{{ $signedAction }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="reject" />
                    <x-mary-button type="submit" class="btn-error btn-sm" :label="ucfirst(__('laravel-crm::lang.reject'))" />
                </form>
            @endif

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
                    {{ ucfirst(__('laravel-crm::lang.issued_to')) }}
                </div>
                <div class="text-sm leading-relaxed">
                    {{ $quote->organization->name ?? $quote->organization->person->name ?? null }}<br />
                    {{ $quote->person->name ?? null }}<br />
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
                    {{ ucfirst(__('laravel-crm::lang.from')) }}
                </div>
                <div class="text-sm leading-relaxed flex items-start justify-between gap-4">
                    <div>{{ $fromName }}</div>
                    @if($logo)
                        <img src="{{ asset('storage/'.$logo) }}" class="max-h-24" alt="" />
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <dl class="grid md:grid-cols-2 gap-x-8 gap-y-1 text-sm pt-2 border-t border-base-content/10">
                    @if($quote->reference)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.reference')) }}</dt>
                            <dd>{{ $quote->reference }}</dd>
                        </div>
                    @endif
                    @if($quote->issue_at)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.issue_date')) }}</dt>
                            <dd>{{ $quote->issue_at->format($dateFormat) }}</dd>
                        </div>
                    @endif
                    @if($quote->expire_at)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.expiry_date')) }}</dt>
                            <dd>{{ $quote->expire_at->format($dateFormat) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        @if($quote->description)
            <div class="pt-4 mt-4 border-t border-base-content/10">
                <h5 class="font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.description')) }}</h5>
                <div class="text-sm">{!! nl2br(e($quote->description)) !!}</div>
            </div>
        @endif

        <div class="pt-4 mt-4 border-t border-base-content/10">
            @livewire('crm-portal-quote-line-items', ['quote' => $quote])
        </div>

        <div class="pt-4 mt-2 flex justify-end">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm min-w-[16rem]">
                <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</dt>
                <dd class="text-right">{{ money($quote->subtotal, $quote->currency) }}</dd>

                @if($quote->discount > 0)
                    <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.discount')) }}</dt>
                    <dd class="text-right">{{ money($quote->discount, $quote->currency) }}</dd>
                @endif

                <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.tax')) }}</dt>
                <dd class="text-right">{{ money($quote->tax, $quote->currency) }}</dd>

                <dt class="font-semibold text-right border-t border-base-content/20 pt-1">{{ ucfirst(__('laravel-crm::lang.total')) }}</dt>
                <dd class="text-right border-t border-base-content/20 pt-1">{{ money($quote->total, $quote->currency) }}</dd>
            </dl>
        </div>

        @if($quote->terms)
            <div class="pt-4 mt-4 border-t border-base-content/10">
                <h5 class="font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.terms')) }}</h5>
                <div class="text-sm">{!! nl2br(e($quote->terms)) !!}</div>
            </div>
        @endif
    </x-mary-card>

@endsection
