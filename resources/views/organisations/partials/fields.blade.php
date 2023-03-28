<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
          'name' => 'name',
          'label' => ucfirst(__('laravel-crm::lang.name')),
          'value' => old('name', $organisation->name ?? null),
          'required' => 'true'
        ])
        @include('laravel-crm::partials.form.select',[
             'name' => 'organisation_type_id',
             'label' => ucfirst(__('laravel-crm::lang.type')),
             'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\OrganisationType::all(), true),
             'value' =>  old('organisation_type_id', $organisation->organisationType->id ?? null),
        ])
        @include('laravel-crm::partials.form.textarea',[
           'name' => 'description',
           'label' => ucfirst(__('laravel-crm::lang.description')),
           'rows' => 5,
           'value' => old('description', $organisation->description ?? null) 
        ])
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
            'value' =>  old('labels', (isset($organisation)) ? $organisation->labels->pluck('id')->toArray() : null)
        ])
        @include('laravel-crm::partials.form.select',[
             'name' => 'user_owner_id',
             'label' => ucfirst(__('laravel-crm::lang.owner')),
             'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
             'value' =>  old('user_owner_id', $organisation->user_owner_id ?? auth()->user()->id),
        ])
    </div>
    <div class="col-sm-6">
        @livewire('phone-edit', [
        'phones' => $phones ?? null,
        'old' => old('phones')
        ])

        @livewire('email-edit', [
        'emails' => $emails ?? null,
        'old' => old('emails')
        ])

        @livewire('address-edit', [
        'addresses' => $addresses ?? null,
        'old' => old('addresses')
        ])
    </div>
</div>