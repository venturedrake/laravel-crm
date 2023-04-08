<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
          'name' => 'name',
          'label' => ucfirst(__('laravel-crm::lang.name')),
          'value' => old('name', $client->name ?? null),
          'required' => true
        ])
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
            'value' =>  old('labels', (isset($client)) ? $client->labels->pluck('id')->toArray() : null)
        ])
        @include('laravel-crm::partials.form.select',[
             'name' => 'user_owner_id',
             'label' => ucfirst(__('laravel-crm::lang.owner')),
             'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
             'value' =>  old('user_owner_id', $client->user_owner_id ?? auth()->user()->id),
             'required' => true
        ])
    </div>
    <div class="col-sm-6">
        
    </div>
</div>