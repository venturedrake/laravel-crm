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

    // Worked out from raw cents, matching InvoiceService / InvoicePay::outstandingCents().
    // The stored amount_due column can be stale on older records.
    $paidCents = (int) $invoice->getRawOriginal('amount_paid');
    $balanceDueCents = $isPaid ? 0 : max(0, (int) $invoice->getRawOriginal('total') - $paidCents);
    $showBalance = $isPaid || $paidCents > 0;
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

    @if($showBalance)
        <div class="grid md:grid-cols-3 gap-5 mb-5">
            <x-mary-stat :title="ucfirst(__('laravel-crm::lang.total'))" :value="money($invoice->total, $invoice->currency)" icon="o-document-text" />
            <x-mary-stat :title="ucfirst(__('laravel-crm::lang.paid'))" :value="money($paidCents, $invoice->currency)" icon="o-check-circle" />
            <x-mary-stat :title="ucfirst(__('laravel-crm::lang.balance_due'))" :value="money($balanceDueCents, $invoice->currency)" icon="o-banknotes" />
        </div>
    @endif

    <div class="bg-white rounded-box shadow overflow-hidden p-6 md:p-10">
        @include('laravel-crm::portal.partials.document-frame')
    </div>

@endsection
