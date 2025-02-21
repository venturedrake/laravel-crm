<div>
    <span class="autocomplete">
        @include('laravel-crm::partials.form.hidden',[
            'name' => 'client_id',
             'attributes' => [
                'wire:model' => 'client_id'        
            ]   
        ])
        <script type="text/javascript">
            let clients = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\clients() !!}
        </script>
        <span wire:ignore>
            @include('laravel-crm::partials.form.text',[
                'name' => 'client_name',
                'label' => ucfirst(__('laravel-crm::lang.client')),
                'prepend' => '<span class="fa fa-address-card" aria-hidden="true"></span>',
                'attributes' => [
                    'autocomplete' => \Illuminate\Support\Str::random(),
                    'wire:model' => 'client_name'  
               ],   
            ])  
        </span>    
    </span>
    
    @if($clientHasOrganizations)

        @include('laravel-crm::partials.form.select',[
            'name' => 'organization_id',
            'label' => ucfirst(__('laravel-crm::lang.organization')),
            'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
            'options' => ['' => ''] + $organizations,
            'attributes' => [
                'wire:model' => 'organization_id'        
            ],
            'required' => 'true',
        ])

    @else

        <span class="autocomplete">
        @include('laravel-crm::partials.form.hidden',[
            'name' => 'organization_id',
             'attributes' => [
                'wire:model' => 'organization_id'        
            ]   
        ])
        <script type="text/javascript">
            let organizations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organizations() !!}
        </script>
            
        <span wire:ignore>    
            @include('laravel-crm::partials.form.text',[
                'name' => 'organization_name',
                'label' => ucfirst(__('laravel-crm::lang.organization')),
                'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
                'attributes' => [
                    'autocomplete' => \Illuminate\Support\Str::random(),
                    'wire:model' => 'organization_name'  
               ],
               'required' => 'true'
            ])  
        </span>    
    </span>
        
    @endif
    
    @if($clientHasPeople)

        @include('laravel-crm::partials.form.select',[
            'name' => 'person_id',
            'label' => ucfirst(__('laravel-crm::lang.contact_person')),
            'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
            'options' => ['' => ''] + $people,
            'attributes' => [
                'wire:model' => 'person_id'        
            ],
            'required' => 'true'
        ])

    @else
        
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
            
            <span wire:ignore>
                 @include('laravel-crm::partials.form.text',[
                    'name' => 'person_name',
                    'label' => ucfirst(__('laravel-crm::lang.contact_person')),
                    'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
                    'attributes' => [
                       'autocomplete' => \Illuminate\Support\Str::random(),
                       'wire:model' => 'person_name'        
                    ],
                    'required' => 'true'
                ])
            </span>
        </span>
    
    @endif

    @push('livewire-js')
        <script>
            $(document).ready(function () {

                bindClientAutocomplete();
                bindPersonAutocomplete();
                bindOrganizationAutocomplete();

                window.addEventListener('clientNameUpdated', event => {
                    bindPersonAutocomplete();
                    bindOrganizationAutocomplete();
                });

                function bindClientAutocomplete(){

                    $('input[name="client_name"]').autocomplete({
                        source: clients,
                        onSelectItem: function (item, element) {
                            @this.set('client_id',item.value);
                            @this.set('client_name',item.label);
                            @this.set('organization_id', $(element).closest('form').find("input[name='organization_id']").val());
                            @this.set('organization_name', $(element).closest('form').find("input[name='organization_name']").val());
                            @this.set('person_id', $(element).closest('form').find("input[name='person_id']").val());
                            @this.set('person_name', $(element).closest('form').find("input[name='person_name']").val());
                            $(element).closest('.autocomplete').find('input[name="client_id"]').val(item.value).trigger('change');
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });

                    $('input[name="client_name"]').on('input', function() {
                        $(this).closest('.autocomplete').find('input[name="client_id"]').val('');
                        $('.autocomplete-client').find('input,select').val('');
                        $(this).closest('.autocomplete').find('input[name="client_id"]').trigger('change');
                    });

                    $('input[name="client_id"]').on('change', function() {
                        if($(this).val() == '' && $.trim($(this).closest('.autocomplete').find('input[name="client_name"]').val()) != ''){
                            $(this).closest('.autocomplete').find(".autocomplete-new").show()
                            $('.autocomplete-client').find('input,select').removeAttr('disabled');
                        }else{
                            $(this).closest('.autocomplete').find(".autocomplete-new").hide()
                            $('.autocomplete-client').find('input,select').attr('disabled','disabled');
                        }
                        @this.set('client_id',$(this).val());
                    });

                    if($('input[name="client_id"]').val() == '' && $.trim($('input[name="client_id"]').closest('.autocomplete').find('input[name="client_name"]').val()) != ''){
                        $('input[name="client_id"]').closest('.autocomplete').find(".autocomplete-new").show()
                        $('.autocomplete-client').find('input,select').removeAttr('disabled');
                    }

                    if($('input[name="client_name"]').closest('.autocomplete').find('input[name="client_id"]').val() == ''){
                        $('.autocomplete-client').find('input,select').removeAttr('disabled');
                    }
                }

                function bindOrganizationAutocomplete(){
                    $('input[name="organization_name"]').autocomplete({
                        source: organizations,
                        onSelectItem: function (item, element) {
                            @this.set('client_id', $(element).closest('form').find("input[name='client_id']").val());
                            @this.set('client_name', $(element).closest('form').find("input[name='client_name']").val());
                            @this.set('person_id', $(element).closest('form').find("input[name='person_id']").val());
                            @this.set('person_name', $(element).closest('form').find("input[name='person_name']").val());
                            @this.set('organization_id', item.value);
                            @this.set('organization_name', item.label);

                            $(element).closest('.autocomplete').find('input[name="organization_id"]').val(item.value).trigger('change');

                            $.ajax({
                                url: "/crm/organizations/" +  item.value + "/autocomplete",
                                cache: false
                            }).done(function( data ) {

                                $('.autocomplete-organization').find('input[name="line1"]').val(data.address_line1);
                                $('.autocomplete-organization').find('input[name="line2"]').val(data.address_line2);
                                $('.autocomplete-organization').find('input[name="line3"]').val(data.address_line3);
                                $('.autocomplete-organization').find('input[name="city"]').val(data.address_city);
                                $('.autocomplete-organization').find('input[name="state"]').val(data.address_state);
                                $('.autocomplete-organization').find('input[name="code"]').val(data.address_code);
                                $('.autocomplete-organization').find('select[name="country"]').val(data.address_country);

                            });
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });

                    $('input[name="organization_name"]').on('input', function() {
                        $(this).closest('.autocomplete').find('input[name="organization_id"]').val('');
                        $('.autocomplete-organization').find('input,select').val('');
                        $(this).closest('.autocomplete').find('input[name="organization_id"]').trigger('change');
                    });

                    $('input[name="organization_id"]').on('change', function() {
                        if($(this).val() == '' && $.trim($(this).closest('.autocomplete').find('input[name="organization_name"]').val()) != ''){
                            $(this).closest('.autocomplete').find(".autocomplete-new").show()
                            $('.autocomplete-organization').find('input,select').removeAttr('disabled');
                            Livewire.emit('orderOrganizationDeselected');
                        }else{
                            $(this).closest('.autocomplete').find(".autocomplete-new").hide()
                            $('.autocomplete-organization').find('input,select').attr('disabled','disabled');
                        }
                        @this.set('organization_id',$(this).val());
                    });

                    if($('input[name="organization_id"]').val() == '' && $.trim($('input[name="organization_id"]').closest('.autocomplete').find('input[name="organization_name"]').val()) != ''){
                        $('input[name="organization_id"]').closest('.autocomplete').find(".autocomplete-new").show();
                        $('.autocomplete-organization').find('input,select').removeAttr('disabled');
                    }

                    if($('input[name="organization_name"]').closest('.autocomplete').find('input[name="organization_id"]').val() == ''){
                        $('.autocomplete-organization').find('input,select').removeAttr('disabled');
                    }
                }

                function bindPersonAutocomplete(){

                    $('input[name="person_name"]').autocomplete({
                        source: people,
                        onSelectItem: function (item, element) {
                            @this.set('client_id', $(element).closest('form').find("input[name='client_id']").val());
                            @this.set('client_name', $(element).closest('form').find("input[name='client_name']").val());
                            @this.set('person_id',item.value);
                            @this.set('person_name',item.label);
                            @this.set('organization_id', $(element).closest('form').find("input[name='organization_id']").val());
                            @this.set('organization_name', $(element).closest('form').find("input[name='organization_name']").val());
                            $(element).closest('.autocomplete').find('input[name="person_id"]').val(item.value).trigger('change');
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });

                    $('input[name="person_name"]').on('input', function() {
                        $(this).closest('.autocomplete').find('input[name="person_id"]').val('');
                        $('.autocomplete-person').find('input,select').val('');
                        $(this).closest('.autocomplete').find('input[name="person_id"]').trigger('change');
                    });

                    $('input[name="person_id"]').on('change', function() {
                        if($(this).val() == '' && $.trim($(this).closest('.autocomplete').find('input[name="person_name"]').val()) != ''){
                            $(this).closest('.autocomplete').find(".autocomplete-new").show()
                            $('.autocomplete-person').find('input,select').removeAttr('disabled');
                        }else{
                            $(this).closest('.autocomplete').find(".autocomplete-new").hide()
                            $('.autocomplete-person').find('input,select').attr('disabled','disabled');
                        }
                        @this.set('person_id',$(this).val());
                    });

                    if($('input[name="person_id"]').val() == '' && $.trim($('input[name="person_id"]').closest('.autocomplete').find('input[name="person_name"]').val()) != ''){
                        $('input[name="person_id"]').closest('.autocomplete').find(".autocomplete-new").show()
                        $('.autocomplete-person').find('input,select').removeAttr('disabled');
                    }

                    if($('input[name="person_name"]').closest('.autocomplete').find('input[name="person_id"]').val() == ''){
                        $('.autocomplete-person').find('input,select').removeAttr('disabled');
                    }
                }
            });
        </script>
    @endpush
</div>
