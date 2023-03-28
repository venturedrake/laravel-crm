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
           ],
           'required' => 'true'
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
            ],
            'required' => 'true'
        ])
    </span>
    
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

                bindPersonAutocomplete();
                bindOrganisationAutocomplete();
                bindTitleUpdate();

                window.addEventListener('updatedNameFieldAutocomplete', event => {
                    bindPersonAutocomplete();
                    bindOrganisationAutocomplete();
                    bindTitleUpdate();
                });
                
                function bindTitleUpdate(){
                    $(document).on("change", "input[name='organisation_name']", function() {
                        if($(this).val() != ''){
                            @this.set('person_id', $(this).closest('form').find("input[name='person_id']").val());
                            @this.set('person_name', $(this).closest('form').find("input[name='person_name']").val());
                            @this.set('organisation_id', $(this).closest('form').find("input[name='organisation_id']").val());
                            @this.set('organisation_name', $(this).closest('form').find("input[name='organisation_name']").val());
                            @this.set('title',  $(this).val() + ' lead');
                            $(this).closest('form').find("input[name='title']").val($(this).val() + ' lead');
                        }
                    });

                    $(document).on("change", "input[name='person_name']", function() {
                        if($(this).closest('form').find("input[name='organisation_name']").val() == '' && $(this).val() != ''){
                            @this.set('person_id', $(this).closest('form').find("input[name='person_id']").val());
                            @this.set('person_name', $(this).closest('form').find("input[name='person_name']").val());
                            @this.set('organisation_id', $(this).closest('form').find("input[name='organisation_id']").val());
                            @this.set('organisation_name', $(this).closest('form').find("input[name='organisation_name']").val());
                            @this.set('title',  $(this).val() + ' lead');
                            $(this).closest('form').find("input[name='title']").val($(this).val() + ' lead');
                        }
                    });
                }

                function bindPersonAutocomplete(){

                    $('input[name="person_name"]').autocomplete({
                        source: people,
                        onSelectItem: function (item, element) {
                            @this.set('person_id',item.value);
                            @this.set('person_name',item.label);
                            @this.set('organisation_id', $(element).closest('form').find("input[name='organisation_id']").val());
                            @this.set('organisation_name', $(element).closest('form').find("input[name='organisation_name']").val());
                            @this.set('title',  $(element).closest('form').find("input[name='title']").val());

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
                            @this.set('organisation_name', item.label);
                            @this.set('title',  $(element).closest('form').find("input[name='title']").val());

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
