<span>
    @can('edit crm invoices')
        <a href="{{ route('laravel-crm.invoices.pay',$this->invoice) }}" data-toggle="modal" data-target="#invoicePayModal_{{ $this->invoice->id }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.pay')) }}</a>
        <div wire:ignore.self class="modal fade" id="invoicePayModal_{{ $this->invoice->id }}" tabindex="-1" aria-labelledby="invoicePayModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="invoicePayModalLabel">{{ ucfirst(__('laravel-crm::lang.pay_invoice')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <x-form wire:submit.prevent="pay">
                        <div class="modal-body text-left">
                            <x-form-input wire:model="amount_paid" name="amount_paid" label="{{ ucfirst(__('laravel-crm::lang.amount')) }}" type="number" step="0.01"> 
                                 @slot('prepend')
                                    <span class="fa fa-dollar" aria-hidden="true"></span>
                                @endslot
                            </x-form-input>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button wire:click.prevent="pay()" type="button" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.pay')) }}</button>
                        </div>
                    </x-form>
                </div>
            </div>
        </div>
    @endcan
    @push('livewire-js')
        <script>
        $(document).ready(function () {
            window.addEventListener('invoicePaid', event => {
                $('#invoicePayModal_{{ $this->invoice->id }}').modal('hide');
                window.location.reload();
            });
        });
    </script>
    @endpush
</span>
