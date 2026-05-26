@extends('laravel-crm::layouts.portal')

@php
    $signedAction = url()->current().'?signature='.request()->input('signature').'&expires='.request()->input('expires');
    $isPaid = (bool) $invoice->fully_paid_at;
    $now = \Carbon\Carbon::now()->timezone($timezone);
    $isExpired = ! $isPaid && $invoice->due_date && $invoice->due_date < $now;

    if ($isPaid) {
        $statusLabel = ucfirst(__('laravel-crm::lang.paid'));
        $statusClass = 'badge-success';
    } elseif ($isExpired) {
        $statusLabel = ucfirst(__('laravel-crm::lang.overdue'));
        $statusClass = 'badge-error';
    } else {
        $statusLabel = ucfirst(__('laravel-crm::lang.unpaid'));
        $statusClass = 'badge-neutral';
    }
@endphp

@section('content')

    <x-mary-header
        :title="money($invoice->total, $invoice->currency).' '.$invoice->currency"
        :subtitle="ucfirst(__('laravel-crm::lang.invoice')).($invoice->reference ? ' · '.$invoice->reference : '')"
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
                    {{ ucfirst(__('laravel-crm::lang.to')) }}
                </div>
                <div class="text-sm leading-relaxed">
                    @if($invoice->organization)
                        {{ $invoice->organization->name }}<br />
                        {{ $invoice->person->name ?? null }}<br />
                    @else
                        {{ $invoice->person->name ?? null }}<br />
                    @endif
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
                    <div>
                        @if($contactDetails)
                            {!! nl2br(e($contactDetails)) !!}
                        @else
                            {{ $fromName }}
                        @endif
                    </div>
                    @if($logo)
                        <img src="{{ asset('storage/'.$logo) }}" class="max-h-24" alt="" />
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <dl class="grid md:grid-cols-2 gap-x-8 gap-y-1 text-sm pt-2 border-t border-base-content/10">
                    @if($invoice->reference)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.invoice_number')) }}</dt>
                            <dd>{{ $invoice->invoice_id }}</dd>
                        </div>
                    @endif
                    @if($invoice->issue_date)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.issued')) }}</dt>
                            <dd>{{ $invoice->issue_date->format($dateFormat) }}</dd>
                        </div>
                    @endif
                    @if($invoice->due_date)
                        <div class="flex gap-2">
                            <dt class="font-semibold">{{ ucfirst(__('laravel-crm::lang.due')) }}</dt>
                            <dd>{{ $invoice->due_date->format($dateFormat) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="pt-4 mt-4 border-t border-base-content/10">
            @livewire('crm-portal-invoice-line-items', ['invoice' => $invoice, 'taxName' => $taxName])
        </div>

        <div class="pt-4 mt-2 flex justify-end">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm min-w-[16rem]">
                <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.sub_total')) }}</dt>
                <dd class="text-right">{{ money($invoice->subtotal, $invoice->currency) }}</dd>

                @if($invoice->discount > 0)
                    <dt class="font-semibold text-right">{{ ucfirst(__('laravel-crm::lang.discount')) }}</dt>
                    <dd class="text-right">{{ money($invoice->discount, $invoice->currency) }}</dd>
                @endif

                <dt class="font-semibold text-right">{{ $taxName }}</dt>
                <dd class="text-right">{{ money($invoice->tax, $invoice->currency) }}</dd>

                <dt class="font-semibold text-right border-t border-base-content/20 pt-1">{{ ucfirst(__('laravel-crm::lang.total')) }}</dt>
                <dd class="text-right border-t border-base-content/20 pt-1">{{ money($invoice->total, $invoice->currency) }}</dd>
            </dl>
        </div>

        @if($paymentInstructions)
            <div class="pt-4 mt-4 border-t border-base-content/10">
                <h5 class="font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.payment')) }}</h5>
                <div class="text-sm">{!! nl2br(e($paymentInstructions)) !!}</div>
            </div>
        @endif

        @if($invoice->terms)
            <div class="pt-4 mt-4 border-t border-base-content/10">
                <h5 class="font-semibold mb-1">{{ ucfirst(__('laravel-crm::lang.terms')) }}</h5>
                <div class="text-sm">{!! nl2br(e($invoice->terms)) !!}</div>
            </div>
        @endif
    </x-mary-card>

@endsection
