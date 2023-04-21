<div class="addresses">
    <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.addresses')) }}</span> @if(!in_array($model, ['order','delivery']))<span class="float-right"><button class="btn btn-outline-secondary btn-sm" wire:click.prevent="add({{$i}})"><span class="fa fa-plus" aria-hidden="true"></span></button></span>@endif</h6>
    <hr />
    @foreach($inputs as $key => $value)
        <input type="hidden" wire:model="addressId.{{ $value }}" name="addresses[{{ $value }}][id]">
        <div class="form-row">
            <div class="@if(!in_array($model, ['order','delivery'])) col-sm-10 @else col-sm-12 @endif">

                <div class="form-group">
                    <label>{{ ucfirst(__('laravel-crm::lang.type')) }}</label>
                    @if(in_array($model, ['order','delivery']))
                        <input type="hidden" class="form-control" wire:model="type.{{ $value }}" name="addresses[{{ $value }}][type]">
                        <input type="text" class="form-control" wire:model="type_name.{{ $value }}" name="addresses[{{ $value }}][type_name]" readonly="readonly">
                    @else    
                        <select class="form-control custom-select" wire:model="type.{{ $value }}" name="addresses[{{ $value }}][type]" @if(in_array($model, ['order','delivery'])) disabled="disabled" @endif>
                            @foreach(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\AddressType::all()) as $optionKey => $optionName)
                                <option value="{{ $optionKey }}">{{ $optionName }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('type.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>

                {{--  <div class="form-group">
                      <label>{{ ucfirst(__('laravel-crm::lang.address')) }}</label>
                      <input type="text" class="form-control" wire:model="address.{{ $value }}" name="addresses[{{ $value }}][address]">
                      @error('address.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                  </div>--}}

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>{{ ucfirst(__('laravel-crm::lang.contact_name')) }}</label>
                            <input type="text" class="form-control" wire:model="contact.{{ $value }}" name="addresses[{{ $value }}][contact]">
                            @error('contact.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>{{ ucfirst(__('laravel-crm::lang.contact_phone')) }}</label>
                            <input type="text" class="form-control" wire:model="phone.{{ $value }}" name="addresses[{{ $value }}][phone]">
                            @error('phone.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ ucfirst(__('laravel-crm::lang.line_1')) }}</label>
                    <input type="text" class="form-control" wire:model="line1.{{ $value }}" name="addresses[{{ $value }}][line1]">
                    @error('line1.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>{{ ucfirst(__('laravel-crm::lang.line_2')) }}</label>
                    <input type="text" class="form-control" wire:model="line2.{{ $value }}" name="addresses[{{ $value }}][line2]">
                    @error('line2.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>{{ ucfirst(__('laravel-crm::lang.line_3')) }}</label>
                    <input type="text" class="form-control" wire:model="line3.{{ $value }}" name="addresses[{{ $value }}][line3]">
                    @error('line3.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>{{ ucfirst(__('laravel-crm::lang.suburb')) }}</label>
                            <input type="text" class="form-control" wire:model="city.{{ $value }}" name="addresses[{{ $value }}][city]">
                            @error('city.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>{{ ucfirst(__('laravel-crm::lang.state')) }}</label>
                            <input type="text" class="form-control" wire:model="state.{{ $value }}" name="addresses[{{ $value }}][state]">
                            @error('state.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>{{ ucfirst(__('laravel-crm::lang.postcode')) }}</label>
                            <input type="text" class="form-control" wire:model="code.{{ $value }}" name="addresses[{{ $value }}][code]">
                            @error('code.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>{{ ucfirst(__('laravel-crm::lang.country')) }}</label>
                            <select class="form-control custom-select" wire:model="country.{{ $value }}" name="addresses[{{ $value }}][country]">
                                @foreach(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\countries() as $optionKey => $optionName)
                                    <option value="{{ $optionKey }}">{{ $optionName }}</option>
                                @endforeach
                            </select>
                            @error('country.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </div>
            @if(!in_array($model, ['order','delivery']))
            <div class="col-sm-1">
                <div class="form-group" wire:ignore>
                    <label>{{ ucfirst(__('laravel-crm::lang.primary')) }}</label>
                    <input type="checkbox" wire:model="primary.{{ $value }}" name="addresses[{{ $value }}][primary]" data-toggle="toggle" data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                    @error('primary.'.$value) <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-sm-1 text-right">
                <div class="form-group">
                    <button class="btn btn-danger btn-sm" wire:click.prevent="remove({{$key}})"><span class="fa fa-trash-o" aria-hidden="true"></span></button></span>
                </div>
            </div>
            @endif
            </div>
@if(!$loop->last)
<hr />
@endif
@endforeach
@push('livewire-js')
<script>
$(document).ready(function () {
    window.addEventListener('addAddressInputs', event => {
        $('input[type=checkbox][data-toggle^=toggle]').bootstrapToggle('destroy').bootstrapToggle('refresh');
    });
});
</script>
@endpush
</div>