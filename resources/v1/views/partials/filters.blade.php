<form method="post" action="{{ $action }}" class="form-inline float-left mr-1">
    @csrf
    <a class="btn btn-sm {{ ($model::anyFilterActive([
    'user_owner_id' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false) + [0 => '(Blank)'],
    'label_id' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)']
    ])) ? 'btn-outline-success' : 'btn-outline-secondary' }}" data-toggle="modal" data-target="#searchFilterModal">{{ ucfirst(__('laravel-crm::lang.filter')) }}</a>
    <div class="modal" id="searchFilterModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ ucfirst(__('laravel-crm::lang.filters')) }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-filter-group mb-2 {{ ($model::filterActive('user_owner_id', \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false) + [0 => '(Blank)'])) ? 'filter-active' : null }}">
                        @include('laravel-crm::partials.filter',[
                            'filter' => $owners ?? null,
                            'name' => 'user_owner_id',
                            'label' => 'owner',
                            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false) + [0 => '(Blank)'],
                            'value' => request()->input('user_owner_id') ?? $model::filterValue('user_owner_id') ?? array_keys(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false) + [0 => '(Blank)'])
                        ])
                    </div>
                    <div class="modal-filter-group {{ ($model::filterActive('label_id', \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'])) ? 'filter-active' : null }}">
                        @include('laravel-crm::partials.filter',[
                            'filter' => $labels ?? null,
                            'name' => 'label_id',
                            'label' => 'label',
                            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'],
                            'value' => request()->input('label_id') ?? $model::filterValue('label_id')  ?? array_keys(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'])
                        ])
                    </div>      
                </div>
                <div class="modal-footer">
                    {{--<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>--}}
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-filter">{{ ucfirst(__('laravel-crm::lang.clear')) }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ ucfirst(__('laravel-crm::lang.filter')) }}</button>
                </div>
            </div>
        </div>
    </div>
    
</form>


