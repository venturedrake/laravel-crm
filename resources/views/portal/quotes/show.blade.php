@extends('laravel-crm::layouts.portal', ['hideNav' => true])

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

    <div class="bg-white rounded-box shadow overflow-hidden p-6 md:p-10">
        @include('laravel-crm::portal.partials.document-frame')
    </div>

@endsection
