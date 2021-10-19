<span class="browse-filter">
     @include('laravel-crm::partials.form.multiselect',[
        'name' => $name,
        'label' => ucfirst(__('laravel-crm::lang.'.$label)),
        'options' => $options,      
        'value' =>  old($name, $value ?? null)
    ])
</span>