<span>
    @can('edit crm quotes')
        <a href="{{ route('laravel-crm.quotes.send',$this->quote) }}" data-toggle="modal" data-target="#quoteSendModal_{{ $this->quote->id }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.send')) }}</a>
        <div wire:ignore.self class="modal fade" id="quoteSendModal_{{ $this->quote->id }}" tabindex="-1" aria-labelledby="quoteSendModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="quoteSendModalLabel">{{ ucfirst(__('laravel-crm::lang.send_quote')) }} </h5>
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
            window.addEventListener('quoteSent', event => {
                $('#quoteSendModal_{{ $this->quote->id }}').modal('hide');
            });
        });
    </script>
    @endpush
</span>
