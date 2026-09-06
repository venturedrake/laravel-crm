@extends('laravel-crm::layouts.portal', ['hideNav' => true])

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

    <div class="bg-white rounded-box shadow overflow-hidden p-6 md:p-10">
        @include('laravel-crm::portal.partials.document-frame')
    </div>

@endsection
