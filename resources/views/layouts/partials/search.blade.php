<form role="search" method="post" action="@php

 if(strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0){
    echo url(route('laravel-crm.leads.search'));
    $currentAction = ucfirst(__('laravel-crm::lang.leads'));
 }elseif(strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0){
     echo url(route('laravel-crm.deals.search'));
     $currentAction = ucfirst(__('laravel-crm::lang.deals'));
 }elseif(strpos(Route::currentRouteName(), 'laravel-crm.people') === 0){
     echo url(route('laravel-crm.people.search'));
     $currentAction = ucfirst(__('laravel-crm::lang.people'));
 }elseif(strpos(Route::currentRouteName(), 'laravel-crm.organisations') === 0){
     echo url(route('laravel-crm.organisations.search'));
     $currentAction = ucfirst(__('laravel-crm::lang.organizations'));
 }elseif(strpos(Route::currentRouteName(), 'laravel-crm.products') === 0){
     echo url(route('laravel-crm.products.search'));
     $currentAction = ucfirst(__('laravel-crm::lang.products'));
 }

@endphp" name="formSearch" class="navbar-form-custom">
    @csrf
    <input type="hidden" name="type" value="{!! Route::current()->getName() !!}">
    <div class="input-group">
        <input type="text" class="form-control" name="search" aria-label="Search" value="{{ old('search') ?? Request::input('search') }}">
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i> <span class="action-current">{{ $currentAction ?? ucfirst(__('laravel-crm::lang.leads')) }}</span></button>
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="#leads" data-type="leads" data-action="{{ url(route('laravel-crm.leads.search')) }}">{{ ucfirst(__('laravel-crm::lang.leads')) }}</a>
                <a class="dropdown-item" href="#deals" data-type="deals" data-action="{{ url(route('laravel-crm.deals.search')) }}">{{ ucfirst(__('laravel-crm::lang.deals')) }}</a>
                <a class="dropdown-item" href="#people" data-type="people" data-action="{{ url(route('laravel-crm.people.search')) }}">{{ ucfirst(__('laravel-crm::lang.people')) }}</a>
                <a class="dropdown-item" href="#organisations" data-type="organisations" data-action="{{ url(route('laravel-crm.organisations.search')) }}">{{ ucfirst(__('laravel-crm::lang.organizations')) }}</a>
                <a class="dropdown-item" href="#products" data-type="products" data-action="{{ url(route('laravel-crm.products.search')) }}">{{ ucfirst(__('laravel-crm::lang.products')) }}</a>
            </div>
        </div>
    </div>
</form>