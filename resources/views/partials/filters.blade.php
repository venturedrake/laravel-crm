<form method="post" action="{{ $action }}" class="form-inline float-right ml-3">
    @csrf
    <div class="form-row">
        <div class="col {{ ($model::filterActive('user_owner_id', \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false) + [0 => '(Blank)'])) ? 'filter-active' : null }}">
            @include('laravel-crm::partials.filter',[
                'filter' => $owners ?? null,
                'name' => 'user_owner_id',
                'label' => 'owner',
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false) + [0 => '(Blank)'],
                'value' => request()->input('user_owner_id') ?? $model::filterValue('user_owner_id') ?? array_keys(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'])
            ])
        </div>
        <div class="col {{ ($model::filterActive('label_id', \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'])) ? 'filter-active' : null }}">
            @include('laravel-crm::partials.filter',[
                'filter' => $labels ?? null,
                'name' => 'label_id',
                'label' => 'label',
                'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'],
                'value' => request()->input('label_id') ?? $model::filterValue('label_id')  ?? array_keys(\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false) + [0 => '(Blank)'])
            ])
        </div>
        <div class="col">
            <button type="submit" class="btn btn-primary btn-sm">{{ ucfirst(__('laravel-crm::lang.filter')) }}</button>
        </div>
    </div>
</form>
