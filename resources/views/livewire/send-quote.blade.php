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
                    <form wire:submit.prevent="share">
                        <div class="modal-body text-left">
                            TO, SUBJECT, MESSAGE, CC
                           {{-- <p>Anyone with this link <strong>can view</strong>. The link will expire after 14 days.</p>
                            <x-form-input wire:model="email" name="email" label="Email" placeholder="Send link to an email" />
                            <p><strong><span class="fa fa-link" aria-hidden="true"></span> <a wire:click.prevent="generateUrl()" href="#">Create</a> and copy link</strong></p>
                            @if($this->signedUrl)
                                <div class="bg-light p-3">
                                    {{ $this->signedUrl }}
                                </div>
                            @endif    --}}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button wire:click.prevent="send()" type="button" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.send')) }}</button>
                        </div>
                    </form>
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
