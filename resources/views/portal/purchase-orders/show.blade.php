@extends('laravel-crm::layouts.portal', ['hideNav' => true])

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

    <div class="bg-white rounded-box shadow overflow-hidden p-6 md:p-10">
        @include('laravel-crm::portal.partials.document-frame')
    </div>

@endsection
