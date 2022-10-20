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
                        <hr />
                        <a class="btn btn-success" href="{{ route('laravel-crm.integrations.xero.disconnect') }}">
                            Disconnect xero
                        </a>
                        <hr />
                        <h4 class="mb-3">Settings</h4>
                        <table class="table mb-0 card-table table-hover">
                            <tbody>
                                <tr>
                                    <td>Sync Contacts</td>
                                    <td class="disable-link text-right">
                                        <input id="sync_contacts" type="checkbox" name="sync_contacts" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Sync Products</td>
                                    <td class="disable-link text-right">
                                        <input id="sync_products" type="checkbox" name="sync_products" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Create & Update Quotes</td>
                                    <td class="disable-link text-right">
                                        <input id="create_quotes" type="checkbox" name="create_quotes" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Create & Update Invoices</td>
                                    <td class="disable-link text-right">
                                        <input id="create_invoices" type="checkbox" name="create_invoices" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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