<span>
    @can('edit crm invoices')
        <a href="{{ route('laravel-crm.invoices.send',$this->invoice) }}" data-toggle="modal" data-target="#invoiceSendModal_{{ $this->invoice->id }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.send')) }}</a>
        <div wire:ignore.self class="modal fade" id="invoiceSendModal_{{ $this->invoice->id }}" tabindex="-1" aria-labelledby="invoiceSendModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="invoiceSendModalLabel">{{ ucfirst(__('laravel-crm::lang.send_invoice')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <x-form wire:submit.prevent="send">
                        <div class="modal-body text-left">
                            <x-form-input wire:model="to" name="to" label="To" />
                            <x-form-input wire:model="subject" name="subject" label="Subject" />
                            <x-form-textarea wire:model="message" name="message" label="Message" rows="10" />
                            <x-form-checkbox wire:model="cc" name="cc" label="Send me a copy" />
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button wire:click.prevent="send()" type="button" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.send')) }}</button>
                        </div>
                    </x-form>
                </div>
            </div>
        </div>
    @endcan
    @push('livewire-js')
        <script>
        $(document).ready(function () {
            window.addEventListener('invoiceSent', event => {
                $('#invoiceSendModal_{{ $this->invoice->id }}').modal('hide');
            });
        });
    </script>
    @endpush
</span>
