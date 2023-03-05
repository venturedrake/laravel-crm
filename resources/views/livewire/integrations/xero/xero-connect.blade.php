<div>
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
                        <form wire:submit.prevent="updateSettings">
                            <table class="table mb-0 card-table table-hover">
                                <tbody>
                                    <tr>
                                        <td>Sync Contacts</td>
                                        <td wire:ignore class="disable-link text-right">
                                            <input wire:model="setting_contacts" id="setting_contacts" type="checkbox" name="setting_contacts" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Sync Products</td>
                                        <td wire:ignore class="disable-link text-right">
                                            <input wire:model="setting_products" id="setting_products" type="checkbox" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                        </td>
                                    </tr>
                                    {{--<tr>
                                        <td>Create & Update Quotes</td>
                                        <td wire:ignore class="disable-link text-right">
                                            <input wire:model="setting_quotes" id="setting_quotes" type="checkbox" name="setting_quotes" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                        </td>
                                    </tr>--}}
                                    <tr>
                                        <td>Create & Update Invoices</td>
                                        <td wire:ignore class="disable-link text-right">
                                            <input wire:model="setting_invoices" id="setting_invoices" type="checkbox" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <hr />
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.save_changes')) }}</button>
                            </div>
                        </form>
                    @else
                        <a type="button" class="btn btn-outline-secondary" href="{{ route('laravel-crm.integrations.xero.connect') }}">
                            Connect to xero
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $('#setting_contacts').change(function() {
                    @this.set('setting_contacts', $(this).prop('checked'));
                })

                $('#setting_products').change(function() {
                    @this.set('setting_products', $(this).prop('checked'));
                })

                $('#setting_quotes').change(function() {
                    @this.set('setting_quotes', $(this).prop('checked'));
                })

                $('#setting_invoices').change(function() {
                    @this.set('setting_invoices', $(this).prop('checked'));
                })
            });
        </script>
    @endpush
</div>