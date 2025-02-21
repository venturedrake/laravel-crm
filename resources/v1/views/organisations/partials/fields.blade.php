<div class="row">
    <div class="col-sm-6 border-right">
        @include('laravel-crm::partials.form.text',[
          'name' => 'name',
          'label' => ucfirst(__('laravel-crm::lang.name')),
          'value' => old('name', $organization->name ?? null),
          'required' => 'true'
        ])
        <div class="row">
            <div class="col">
                @include('laravel-crm::partials.form.select',[
                     'name' => 'organization_type_id',
                     'label' => ucfirst(__('laravel-crm::lang.type')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\OrganizationType::all(), true),
                     'value' =>  old('organization_type_id', $organization->organizationType->id ?? null),
                ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                  'name' => 'vat_number',
                  'label' => ucfirst(__('laravel-crm::lang.vat_number')),
                  'value' => old('vat_number', $organization->vat_number ?? null),       
                ])
            </div>
        </div>

        <div class="row">
            <div class="col">
                @include('laravel-crm::partials.form.select',[
                     'name' => 'industry_id',
                     'label' => ucfirst(__('laravel-crm::lang.industry')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Industry::all(), true),
                     'value' =>  old('industry_id', $organization->industry->id ?? null),
                ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.select',[
                     'name' => 'timezone_id',
                     'label' => ucfirst(__('laravel-crm::lang.timezone')),
                     'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Timezone::all(), true),
                     'value' =>  old('timezone_id', $organization->timezone->id ?? null),
                ])
            </div>
        </div>

        <div class="row">
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                   'name' => 'number_of_employees',
                   'label' => ucfirst(__('laravel-crm::lang.number_of_employees')),
                   'value' => old('number_of_employees', $organization->number_of_employees ?? null),     
                 ])
            </div>
            <div class="col">
                @include('laravel-crm::partials.form.text',[
                    'name' => 'annual_revenue',
                    'label' => ucfirst(__('laravel-crm::lang.annual_revenue')),
                    'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>',
                    'value' => old('annual_revenue', ((isset($organization->annual_revenue)) ? ($organization->annual_revenue / 100) : null) ?? null)      
                  ])
            </div>
        </div>

        @include('laravel-crm::partials.form.text',[
             'name' => 'linkedin',
             'label' => ucfirst(__('laravel-crm::lang.linkedin_company_page')),
             'prepend' => 'https://www.linkedin.com/company/',
             'value' => old('linkedin', $organization->linkedin ?? null),     
           ])

        @include('laravel-crm::partials.form.textarea',[
           'name' => 'description',
           'label' => ucfirst(__('laravel-crm::lang.description')),
           'rows' => 5,
           'value' => old('description', $organization->description ?? null) 
        ])
        @include('laravel-crm::partials.form.multiselect',[
            'name' => 'labels',
            'label' => ucfirst(__('laravel-crm::lang.labels')),
            'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\optionsFromModel(\VentureDrake\LaravelCrm\Models\Label::all(), false),      
            'value' =>  old('labels', (isset($organization)) ? $organization->labels->pluck('id')->toArray() : null)
        ])
        @include('laravel-crm::partials.form.select',[
             'name' => 'user_owner_id',
             'label' => ucfirst(__('laravel-crm::lang.owner')),
             'options' => ['' => ucfirst(__('laravel-crm::lang.unallocated'))] + \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
             'value' =>  old('user_owner_id', (isset($organization)) ? $organization->user_owner_id ?? '' : auth()->user()->id),
        ])

        @include('laravel-crm::fields.partials.model', ['model' => $organization ?? new \VentureDrake\LaravelCrm\Models\Organization()])
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