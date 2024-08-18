<div>
    <form wire:submit.prevent="submit">
        <div class="container-fluid pl-0">
            <div class="row">
                <div class="col col-md-2">
                    <div class="card">
                        <div class="card-body py-3 px-2">
                            @include('laravel-crm::layouts.partials.nav-settings')
                        </div>
                    </div>
                </div>
                <div class="col col-md-10">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title float-left m-0">
                                @if($field)
                                    {{ ucfirst(trans('laravel-crm::lang.edit_custom_field')) }}
                                @else
                                    {{ ucfirst(trans('laravel-crm::lang.create_custom_field')) }}
                                @endif
                            </h3>
                            <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.fields.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(trans('laravel-crm::lang.back_to_fields')) }}</a></span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6 border-right">
                                    @include('laravel-crm::partials.form.text',[
                                     'name' => 'fieldName',
                                     'label' => ucfirst(trans('laravel-crm::lang.name')),
                                     'attributes' => [
                                        'wire:model' => 'fieldName'  
                                     ],
                                     'required' => 'true'
                                    ])

                                    @include('laravel-crm::partials.form.select',[
                                    'name' => 'fieldType',
                                    'label' => ucfirst(trans('laravel-crm::lang.type')),
                                    'options' => [
                                       'text' => 'Single-line text',
                                       'textarea' => 'Multi-line text',
                                       'checkbox' => 'Single checkbox',
                                       'checkbox_multiple' => 'Multiple checkbox',
                                       'select' => 'Dropdown select',
                                       'radio' => 'Radio select', 
                                       'date' => 'Date picker',
                                     ],
                                    'attributes' => [
                                        'wire:model' => 'fieldType'  
                                    ],
                                    'required' => 'true'
                                    ])

                                    @switch($fieldType)
                                        @case('select')
                                        @case('checkbox_multiple')
                                        @case('radio')
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    Options
                                                </div>
                                                <div class="card-body p-0">
                                                    <table class="table table-items pb-0 mb-0">
                                                        <thead>
                                                        <tr>
                                                            <th>Label</th>
                                                            <th>Order</th>
                                                            <th></th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($fieldOptions as $key => $option)
                                                            <tr wire:key="option-{{ $key }}">
                                                                <td>
                                                                    @include('laravel-crm::partials.form.text',[
                                                                       'name' => 'options['.$key.'][label]',
                                                                       'type' => 'text',
                                                                       'attributes' => [
                                                                           'wire:model' => 'fieldOptions.'.$key.'.label',
                                                                       ]
                                                                    ])
                                                                </td>
                                                                <td width="80">
                                                                    @include('laravel-crm::partials.form.text',[
                                                                       'name' => 'options['.$key.'][order]',
                                                                       'type' => 'number',
                                                                       'attributes' => [
                                                                           'wire:model' => 'fieldOptions.'.$key.'.order',
                                                                       ]
                                                                    ])
                                                                </td>
                                                                <td width="80">
                                                                    <button wire:click.prevent="removeOption({{ $key }})" type="button" class="btn btn-outline-danger btn-sm btn-close"><span class="fa fa-remove"></span></button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                        <tfoot>
                                                        <tr>
                                                            <td colspan="3"><button class="btn btn-outline-secondary btn-sm" wire:click.prevent="addOption()"><span class="fa fa-plus" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.add_option')) }}</button></td>
                                                        </tr>
                                                        </tfoot>
                                                    </table>
                                                    {{--   <h4 class="card-title">Special title treatment</h4>
                                                       <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                                                       <a href="javascript:void(0)" class="btn btn-primary">Go somewhere</a>--}}

                                                </div>
                                            </div>

                                            @break
                                    @endswitch

                                    @include('laravel-crm::partials.form.select',[
                                    'name' => 'fieldGroup',
                                    'label' => ucfirst(trans('laravel-crm::lang.group')),
                                    'options' => [''=>''] + \VentureDrake\LaravelCrm\Models\FieldGroup::pluck('name','id')->toArray(),
                                    'attributes' => [
                                        'wire:model' => 'fieldGroup'  
                                    ],
                                    ])

                                    @include('laravel-crm::partials.form.text',[
                                     'name' => 'fieldDefault',
                                     'label' => ucfirst(trans('laravel-crm::lang.default')),
                                     'attributes' => [
                                        'wire:model' => 'fieldDefault'  
                                     ],
                                    ])

                                    <div wire:ignore class="form-group">
                                        <label for="required">{{ ucfirst(__('laravel-crm::lang.required')) }}</label>
                                        <span class="form-control-toggle">
                             <input wire:model="fieldRequired" id="fieldRequired" type="checkbox" name="fieldRequired" {{ (isset($field) && $field->required == 1) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                            </span>
                                    </div>
                                </div>
                                <div wire:ignore class="col-6">
                                    <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.attach')) }}</h6>
                                    @include('laravel-crm::partials.form.multiselect',[
                                    'name' => 'field_models',
                                    'label' => null,
                                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\fieldModels(),
                                    'value' => old('field_models', (isset($field)) ? \VentureDrake\LaravelCrm\Models\FieldModel::where('field_id', $field->id)->get()->pluck('model')->toArray() : null),
                                    'attributes' => [
                                        'wire:model' => 'fieldModels'  
                                     ],
                                  ])
                                </div>
                            </div>
                        </div>
                        @component('laravel-crm::components.card-footer')
                            <a href="{{ url(route('laravel-crm.fields.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(trans('laravel-crm::lang.cancel')) }}</a>
                            <button type="submit" class="btn btn-primary">
                                @if($field)
                                    {{ ucfirst(trans('laravel-crm::lang.save_changes')) }}
                                @else
                                    {{ ucfirst(trans('laravel-crm::lang.save')) }}
                                @endif
                            </button>
                        @endcomponent
                    </div>
                </div>
            </div>
        </div>
        
    </form>
    
    @push('livewire-js')
        <script>
            $(function() {
                $('#fieldRequired').change(function() {
                    @this.set('fieldRequired', $(this).prop('checked'));
                })

                $('#select_field_models').change(function() {
                    @this.set('fieldModels', $(this).val());
                })
            })
        </script>
    @endpush    
</div>
