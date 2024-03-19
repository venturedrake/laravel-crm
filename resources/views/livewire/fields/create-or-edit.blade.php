<div>
    <form wire:submit.prevent="submit">
        <div class="card">
            <div class="card-header">
                @include('laravel-crm::layouts.partials.nav-settings')
            </div>
            <div class="card-body">
                <h3 class="mb-3">{{ ucfirst(trans('laravel-crm::lang.create_field')) }} <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.fields.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(trans('laravel-crm::lang.back_to_fields')) }}</a></span></h3>
                <div class="row">
                    <div class="col-sm-6 border-right">
                        @include('laravel-crm::partials.form.select',[
                        'name' => 'type',
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
                            'wire:model' => 'type'  
                        ],
                        ])
                        
                        @switch($type)
                            @case('select')
                                <div class="card mb-3">
                                    <div class="card-header">
                                        Options
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table pb-0 mb-0">
                                            <thead>
                                            <tr>
                                                <th>Label</th>
                                                <th>Order</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            
                                            @foreach($options as $option)
                                                <tr>
                                                    <td>
                                                        Text
                                                    </td>
                                                    <td>
                                                        X
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td colspan="3">+ Add option</td>
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
                        'name' => 'field_group_id',
                        'label' => ucfirst(trans('laravel-crm::lang.group')),
                        'options' => [''=>''] + \VentureDrake\LaravelCrm\Models\FieldGroup::pluck('name','id')->toArray(),
                        'attributes' => [
                            'wire:model' => 'fieldGroup'  
                        ],
                        ])

                        @include('laravel-crm::partials.form.text',[
                         'name' => 'name',
                         'label' => ucfirst(trans('laravel-crm::lang.name')),
                         'attributes' => [
                            'wire:model' => 'name'  
                         ],
                        ])

                        @include('laravel-crm::partials.form.text',[
                         'name' => 'default',
                         'label' => ucfirst(trans('laravel-crm::lang.default')),
                         'attributes' => [
                            'wire:model' => 'default'  
                         ],
                        ])

                        <div class="form-group">
                            <label for="required">{{ ucfirst(__('laravel-crm::lang.required')) }}</label>
                            <span class="form-control-toggle">
                             <input id="required" type="checkbox" name="required" {{ (isset($field) && $field->required == 1) ? 'checked' : null }} data-toggle="toggle" data-size="sm" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger">
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.attach')) }}</h6>
                        @include('laravel-crm::partials.form.multiselect',[
                        'name' => 'field_models',
                        'label' => null,
                        'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\fieldModels(),
                        'value' => old('field_models', (isset($field)) ? \VentureDrake\LaravelCrm\Models\FieldModel::where('field_id', $field->id)->get()->pluck('model')->toArray() : null)
                      ])
                    </div>
                </div>
            </div>
            @component('laravel-crm::components.card-footer')
                <a href="{{ url(route('laravel-crm.fields.index')) }}" class="btn btn-outline-secondary">{{ ucfirst(trans('laravel-crm::lang.cancel')) }}</a>
                <button type="submit" class="btn btn-primary">{{ ucfirst(trans('laravel-crm::lang.save')) }}</button>
            @endcomponent
        </div>
    </form>
</div>
