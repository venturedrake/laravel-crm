<div>
    <span class="autocomplete">
        @include('laravel-crm::partials.form.hidden',[
            'name' => 'organisation_id',
             'attributes' => [
                'wire:model' => 'organisation_id'        
            ]   
        ])
        <script type="text/javascript">
            let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
        </script>
        @include('laravel-crm::partials.form.text',[
            'name' => 'organisation_name',
            'label' => ucfirst(__('laravel-crm::lang.organization')),
            'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
            'attributes' => [
                'autocomplete' => \Illuminate\Support\Str::random(),
                'wire:model.debounce.10000ms' => 'organisation_name'  
           ]
        ])  
    </span>
    
    <span class="autocomplete">
       @include('laravel-crm::partials.form.hidden',[
           'name' => 'person_id',
           'attributes' => [
                'wire:model' => 'person_id'        
            ]   
        ])
       <script type="text/javascript">
        let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\people() !!}
       </script>
         @include('laravel-crm::partials.form.text',[
            'name' => 'person_name',
            'label' => ucfirst(__('laravel-crm::lang.contact_person')),
            'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
            'attributes' => [
               'autocomplete' => \Illuminate\Support\Str::random(),
               'wire:model.debounce.10000ms' => 'person_name'        
            ]
        ])
    </span>
    
    @include('laravel-crm::partials.form.text',[
        'name' => 'title',
        'label' => ucfirst(__('laravel-crm::lang.title')),
        'attributes' => [
            'wire:model' => 'title'        
        ]   
    ])
</div>
