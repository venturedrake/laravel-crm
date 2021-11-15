<div class="phone-numbers">
    <h6 class="text-uppercase section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.phone_numbers')) }}</span> <span class="float-right"><button class="btn btn-outline-secondary btn-sm" wire:click.prevent="add({{$i}})"><span class="fa fa-plus" aria-hidden="true"></span></button></span></h6>
    <hr />
    @foreach($inputs as $key => $value)
        <input type="hidden" wire:model="phoneId.{{ $value }}" name="phones[{{ $value }}][id]">
        <div class="form-row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>{{ ucfirst(__('laravel-crm::lang.phone')) }}</label>
                    <input type="text" class="form-control" wire:model="number.{{ $value }}" name="phones[{{ $value }}][number]">
                    @error('number.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>{{ ucfirst(__('laravel-crm::lang.type')) }}</label>
                    <select class="form-control custom-select" wire:model="type.{{ $value }}" name="phones[{{ $value }}][type]">
                        @foreach(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes() as $optionKey => $optionName)
                            <option value="{{ $optionKey }}">{{ $optionName }}</option>
                        @endforeach
                    </select>
                    @error('type.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-sm-1">
                <div class="form-group" wire:ignore>
                    <label>{{ ucfirst(__('laravel-crm::lang.primary')) }}</label>
                    <input type="checkbox" wire:model="primary.{{ $value }}" name="phones[{{ $value }}][primary]" data-toggle="toggle" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                    @error('primary.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-sm-1 text-right">
                <div class="form-group">
                    <button class="btn btn-danger btn-sm" wire:click.prevent="remove({{$key}})"><span class="fa fa-trash-o" aria-hidden="true"></span></button></span>
                </div>
            </div>
        </div>
    @endforeach
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                window.addEventListener('addPhoneInputs', event => {
                    $('input[type=checkbox][data-toggle^=toggle]').bootstrapToggle('destroy').bootstrapToggle('refresh');
                });
            });
        </script>
    @endpush
</div>


