<div>
    @section('content')
    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="integrations" role="tabpanel">
                    <h3 class="mb-3">Xero</h3>
                    <p class="border-bottom mb-3 pb-3">Connect to xero accounting to sync contacts, products, quotes & generate invoices.</p>
                    @if(isset($tenantName))
                        <div class="alert alert-info">
                            You are connected to the Xero organisation <strong>{{ $tenantName }}</strong>.
                        </div>
                        <a class="btn btn-success" href="{{ route('laravel-crm.integrations.xero.disconnect') }}">
                            Disconnect xero
                        </a>
                    @else
                        <a type="button" class="btn btn-outline-secondary" href="{{ route('laravel-crm.integrations.xero.connect') }}">
                            Connect to xero
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endsection
</div>