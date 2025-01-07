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
               ]
            ])  
        </span>    
    </span>

    @if($clientHasOrganisations)

        @include('laravel-crm::partials.form.select',[
            'name' => 'organisation_id',
            'label' => ucfirst(__('laravel-crm::lang.organization')),
            'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
            'options' => ['' => ''] + $organisations,
            'attributes' => [
                'wire:model' => 'organisation_id'        
            ],
            'required' => 'true',
        ])
        
    @else
        
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
            <span wire:ignore>
                @include('laravel-crm::partials.form.text',[
                    'name' => 'organisation_name',
                    'label' => ucfirst(__('laravel-crm::lang.organization')),
                    'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>',
                    'attributes' => [
                        'autocomplete' => \Illuminate\Support\Str::random(),
                        'wire:model' => 'organisation_name'  
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
    
    @include('laravel-crm::partials.form.text',[
        'name' => 'title',
        'label' => ucfirst(__('laravel-crm::lang.title')),
        'attributes' => [
            'wire:model' => 'title'        
        ],
        'required' => 'true'
    ])

    @push('livewire-js')
        <script>
            $(document).ready(function () {
                bindClientAutocomplete();
                bindPersonAutocomplete();
                bindOrganisationAutocomplete();

                window.addEventListener('clientNameUpdated', event => {
                    bindPersonAutocomplete();
                    bindOrganisationAutocomplete();
                });

                window.addEventListener('selectedOrganisation', event => {
                    if(event.detail.id){
                        $('.autocomplete-organisation').find('input[name="line1"]').val(event.detail.address_line1);
                        $('.autocomplete-organisation').find('input[name="line2"]').val(event.detail.address_line2);
                        $('.autocomplete-organisation').find('input[name="line3"]').val(event.detail.address_line3);
                        $('.autocomplete-organisation').find('input[name="city"]').val(event.detail.address_city);
                        $('.autocomplete-organisation').find('input[name="state"]').val(event.detail.address_state);
                        $('.autocomplete-organisation').find('input[name="code"]').val(event.detail.address_code);
                        $('.autocomplete-organisation').find('select[name="country"]').val(event.detail.address_country);
                        $('.autocomplete-organisation').find('input,select').attr('disabled','disabled');
                    }else{
                        $('.autocomplete-organisation').find('input[name="line1"]').val('');
                        $('.autocomplete-organisation').find('input[name="line2"]').val('');
                        $('.autocomplete-organisation').find('input[name="line3"]').val('');
                        $('.autocomplete-organisation').find('input[name="city"]').val('');
                        $('.autocomplete-organisation').find('input[name="state"]').val('');
                        $('.autocomplete-organisation').find('input[name="code"]').val('');
                        $('.autocomplete-organisation').find('select[name="country"]').val('');
                        $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
                    }
                });

                window.addEventListener('selectedPerson', event => {
                    if(event.detail.id){
                        $('.autocomplete-person').find('input[name="phone"]').val(event.detail.phone);
                        $('.autocomplete-person').find('select[name="phone_type"]').val(event.detail.phone_type);
                        $('.autocomplete-person').find('input[name="email"]').val(event.detail.email);
                        $('.autocomplete-person').find('select[name="email_type"]').val(event.detail.email_type);
                        $('.autocomplete-person').find('input,select').attr('disabled','disabled');
                    }else{
                        $('.autocomplete-person').find('input[name="phone"]').val('');
                        $('.autocomplete-person').find('select[name="phone_type"]').val('');
                        $('.autocomplete-person').find('input[name="email"]').val('');
                        $('.autocomplete-person').find('select[name="email_type"]').val('');
                        $('.autocomplete-person').find('input,select').removeAttr('disabled');
                    }
                });

                function bindClientAutocomplete(){

                    $('input[name="client_name"]').autocomplete({
                        source: clients,
                        onSelectItem: function (item, element) {
                            @this.set('client_id',item.value);
                            @this.set('client_name',item.label);
                            @this.set('organisation_id', $(element).closest('form').find("input[name='organisation_id']").val());
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

                function bindPersonAutocomplete(){

                    $('input[name="person_name"]').autocomplete({
                        source: people,
                        onSelectItem: function (item, element) {
                            @this.set('person_id',item.value);
                            @this.set('person_name',item.label);
                            @this.set('organisation_id', $(element).closest('form').find("input[name='organisation_id']").val());

                            $(element).closest('.autocomplete').find('input[name="person_id"]').val(item.value).trigger('change');

                            $.ajax({
                                url: "/crm/people/" +  item.value + "/autocomplete",
                                cache: false
                            }).done(function( data ) {

                                $('.autocomplete-person').find('input[name="phone"]').val(data.phone);
                                $('.autocomplete-person').find('select[name="phone_type"]').val(data.phone_type);
                                $('.autocomplete-person').find('input[name="email"]').val(data.email);
                                $('.autocomplete-person').find('select[name="email_type"]').val(data.email_type);

                            });
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

                function bindOrganisationAutocomplete(){
                    $('input[name="organisation_name"]').autocomplete({
                        source: organisations,
                        onSelectItem: function (item, element) {
                            @this.set('person_id', $(element).closest('form').find("input[name='person_id']").val());
                            @this.set('person_name', $(element).closest('form').find("input[name='person_name']").val());
                            @this.set('organisation_id', item.value);

                            $(element).closest('.autocomplete').find('input[name="organisation_id"]').val(item.value).trigger('change');

                            $.ajax({
                                url: "/crm/organisations/" +  item.value + "/autocomplete",
                                cache: false
                            }).done(function( data ) {

                                $('.autocomplete-organisation').find('input[name="line1"]').val(data.address_line1);
                                $('.autocomplete-organisation').find('input[name="line2"]').val(data.address_line2);
                                $('.autocomplete-organisation').find('input[name="line3"]').val(data.address_line3);
                                $('.autocomplete-organisation').find('input[name="city"]').val(data.address_city);
                                $('.autocomplete-organisation').find('input[name="state"]').val(data.address_state);
                                $('.autocomplete-organisation').find('input[name="code"]').val(data.address_code);
                                $('.autocomplete-organisation').find('select[name="country"]').val(data.address_country);

                            });
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });

                    $('input[name="organisation_name"]').on('input', function() {
                        $(this).closest('.autocomplete').find('input[name="organisation_id"]').val('');
                        $('.autocomplete-organisation').find('input,select').val('');
                        $(this).closest('.autocomplete').find('input[name="organisation_id"]').trigger('change');
                    });

                    $('input[name="organisation_id"]').on('change', function() {
                        if($(this).val() == '' && $.trim($(this).closest('.autocomplete').find('input[name="organisation_name"]').val()) != ''){
                            $(this).closest('.autocomplete').find(".autocomplete-new").show()
                            $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
                        }else{
                            $(this).closest('.autocomplete').find(".autocomplete-new").hide()
                            $('.autocomplete-organisation').find('input,select').attr('disabled','disabled');
                        }
                        @this.set('organisation_id',$(this).val());
                    });

                    if($('input[name="organisation_id"]').val() == '' && $.trim($('input[name="organisation_id"]').closest('.autocomplete').find('input[name="organisation_name"]').val()) != ''){
                        $('input[name="organisation_id"]').closest('.autocomplete').find(".autocomplete-new").show()
                        $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
                    }

                    if($('input[name="organisation_name"]').closest('.autocomplete').find('input[name="organisation_id"]').val() == ''){
                        $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
                    }
                }
            });
        </script>
    @endpush
</div>
