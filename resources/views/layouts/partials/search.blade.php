<form role="search" method="get" action="@php

     if(strpos(Route::currentRouteName(), 'laravel-crm.leads') === 0 && auth()->user()->can('view crm leads')){
        echo url(route('laravel-crm.leads.search'));
        $currentAction = ucfirst(__('laravel-crm::lang.leads'));
     }elseif(strpos(Route::currentRouteName(), 'laravel-crm.deals') === 0 && auth()->user()->can('view crm deals')){
         echo url(route('laravel-crm.deals.search'));
         $currentAction = ucfirst(__('laravel-crm::lang.deals'));
     }elseif(strpos(Route::currentRouteName(), 'laravel-crm.quotes') === 0 && auth()->user()->can('view crm quotes')){
         echo url(route('laravel-crm.quotes.search'));
         $currentAction = ucfirst(__('laravel-crm::lang.quotes'));
     }elseif(strpos(Route::currentRouteName(), 'laravel-crm.orders') === 0 && auth()->user()->can('view crm orders')){
         echo url(route('laravel-crm.orders.search'));
         $currentAction = ucfirst(__('laravel-crm::lang.orders'));
     }elseif(strpos(Route::currentRouteName(), 'laravel-crm.people') === 0  && auth()->user()->can('view crm people')){
         echo url(route('laravel-crm.people.search'));
         $currentAction = ucfirst(__('laravel-crm::lang.people'));
     }elseif(strpos(Route::currentRouteName(), 'laravel-crm.organisations') === 0  && auth()->user()->can('view crm organisations')){
         echo url(route('laravel-crm.organisations.search'));
         $currentAction = ucfirst(__('laravel-crm::lang.organizations'));
     }elseif(strpos(Route::currentRouteName(), 'laravel-crm.products') === 0  && auth()->user()->can('view crm products')){
         echo url(route('laravel-crm.products.search'));
         $currentAction = ucfirst(__('laravel-crm::lang.products'));
     }
 
    if(!isset($currentAction)){
        if(auth()->user()->can('view crm leads')){
            echo url(route('laravel-crm.leads.search'));
            $currentAction = ucfirst(__('laravel-crm::lang.leads'));
         }elseif(auth()->user()->can('view crm deals')){
             echo url(route('laravel-crm.deals.search'));
             $currentAction = ucfirst(__('laravel-crm::lang.deals'));
         }elseif(auth()->user()->can('view crm quotes')){
             echo url(route('laravel-crm.quotes.search'));
             $currentAction = ucfirst(__('laravel-crm::lang.quotes'));
         }elseif(auth()->user()->can('view crm orders')){
             echo url(route('laravel-crm.orders.search'));
             $currentAction = ucfirst(__('laravel-crm::lang.orders'));
         }elseif(auth()->user()->can('view crm people')){
             echo url(route('laravel-crm.people.search'));
             $currentAction = ucfirst(__('laravel-crm::lang.people'));
         }elseif( auth()->user()->can('view crm organisations')){
             echo url(route('laravel-crm.organisations.search'));
             $currentAction = ucfirst(__('laravel-crm::lang.organizations'));
         }elseif(auth()->user()->can('view crm products')){
             echo url(route('laravel-crm.products.search'));
             $currentAction = ucfirst(__('laravel-crm::lang.products'));
         }
    }

@endphp" name="formSearch" class="navbar-form-custom">
    @csrf
    <input type="hidden" name="type" value="{!! Route::current()->getName() !!}">
    <div class="input-group">
        <input type="text" class="form-control" name="search" aria-label="Search" value="{{ old('search') ?? Request::input('search') ?? $searchValue ?? null }}">
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i> <span class="action-current">{{ $currentAction ?? ucfirst(__('laravel-crm::lang.leads')) }}</span></button>
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
            <div class="dropdown-menu dropdown-menu-right">
                @can('view crm leads')
                <a class="dropdown-item" href="#leads" data-type="leads" data-action="{{ url(route('laravel-crm.leads.search')) }}">{{ ucfirst(__('laravel-crm::lang.leads')) }}</a>
                @endcan
                @can('view crm deals')
                <a class="dropdown-item" href="#deals" data-type="deals" data-action="{{ url(route('laravel-crm.deals.search')) }}">{{ ucfirst(__('laravel-crm::lang.deals')) }}</a>
                @endcan
                @can('view crm quotes')
                    <a class="dropdown-item" href="#quotes" data-type="quotes" data-action="{{ url(route('laravel-crm.quotes.search')) }}">{{ ucfirst(__('laravel-crm::lang.quotes')) }}</a>
                @endcan
                @can('view crm orders')
                    <a class="dropdown-item" href="#orders" data-type="orders" data-action="{{ url(route('laravel-crm.orders.search')) }}">{{ ucfirst(__('laravel-crm::lang.orders')) }}</a>
                @endcan
                @can('view crm people')
                <a class="dropdown-item" href="#people" data-type="people" data-action="{{ url(route('laravel-crm.people.search')) }}">{{ ucfirst(__('laravel-crm::lang.people')) }}</a>
                @endcan
                @can('view crm organisations')    
                <a class="dropdown-item" href="#organisations" data-type="organisations" data-action="{{ url(route('laravel-crm.organisations.search')) }}">{{ ucfirst(__('laravel-crm::lang.organizations')) }}</a>
                @endcan
                @can('view crm products')
                <a class="dropdown-item" href="#products" data-type="products" data-action="{{ url(route('laravel-crm.products.search')) }}">{{ ucfirst(__('laravel-crm::lang.products')) }}</a>
                @endcan
            </div>
        </div>
    </div>
</form>