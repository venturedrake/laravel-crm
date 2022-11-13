@extends('laravel-crm::layouts.portal')

@section('content')

    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top">
            <div class="container-fluid">
                <h1 class="navbar-brand mb-0" href="#">
                    {{ money($quote->total, $quote->currency) }} {{ $quote->currency }}</h1>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item mr-2">
                            <a class="btn btn-outline-success" href="#">Accept</a>
                        </li>
                        <li class="nav-item mr-2">
                            <a class="btn btn-outline-danger" href="#">Reject</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-secondary" href="#">Download</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main role="main" class="flex-shrink-0">
        <div class="container">
            <div class="row card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h1 class="card-title pricing-card-title">Quote</h1>
                        </div>
                        <div class="col">
                            LOGO PLACEHOLDER
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </main>

@endsection