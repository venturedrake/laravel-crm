@extends('laravel-crm::layouts.portal')

@section('content')

    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top"> 
            <div class="container-fluid">
                <h1 class="navbar-brand mb-0" href="#">
                    {{ money($quote->total, $quote->currency) }} <small>{{ $quote->currency }}</small>
                    @if($quote->accepted_at)
                        <small><span class="badge badge-success">{{ ucfirst(__('laravel-crm::lang.accepted')) }}</span></small>
                    @elseif($quote->rejected_at)
                        <small><span class="badge badge-danger">{{ ucfirst(__('laravel-crm::lang.rejected')) }}</span></small>
                    @elseif(\Carbon\Carbon::now() <= $quote->expire_at)
                        <small><span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.expires_in')) }} {{ $quote->expire_at->diffForHumans() }}</span></small>
                    @else
                        <small><span class="badge badge-danger">{{ ucfirst(__('laravel-crm::lang.quote_expired')) }}</span></small>
                    @endif
                </h1>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        @if($quote->accepted_at)
                            <div class="alert alert-success m-0" role="alert">
                                {{ ucfirst(__('laravel-crm::lang.you_have_accepted_this_quote')) }}.
                            </div>
                        @elseif($quote->rejected_at)
                            <div class="alert alert-danger m-0" role="alert">
                                {{ ucfirst(__('laravel-crm::lang.you_have_rejected_this_quote')) }}.
                            </div>
                        @elseif(\Carbon\Carbon::now() <= $quote->expire_at)
                            <li class="nav-item mr-2">
                                <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}" method="POST" class="form-check-inline mr-0">
                                    {{ csrf_field() }}
                                    <x-form-input name="action" value="accept" type="hidden" />
                                    <button class="btn btn-outline-success" type="submit">{{ ucfirst(__('laravel-crm::lang.accept')) }}</button>
                                </form>
                            </li>
                            <li class="nav-item mr-2">
                                <form action="{{ url()->current() }}?signature={{ request()->input('signature') }}" method="POST" class="form-check-inline mr-0">
                                    {{ csrf_field() }}
                                    <x-form-input name="action" value="reject" type="hidden" />
                                    <button class="btn btn-outline-danger" type="submit">{{ ucfirst(__('laravel-crm::lang.reject')) }}</button>
                                </form>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="btn btn-outline-secondary" href="#"><span class="fa fa-download" aria-hidden="true"></span> Download</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main role="main" class="flex-shrink-0">
        <div class="container">
            <div class="row card shadow-sm mb-4">
                <div class="card-body p-0">
                    @include('laravel-crm::quotes.partials.document')
                </div>  
            </div>
        </div>
    </main>

@endsection