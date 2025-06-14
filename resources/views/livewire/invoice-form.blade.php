<div>
    <span class="autocomplete">
        @include('laravel-crm::partials.form.hidden',[
            'name' => 'organisation_id',
             'attributes' => [
                'wire:model' => 'organisation_id'        
            ]   
        ])
        <script type="text/javascript">
            let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisationsWithDetails() !!}
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

    <span class="autocomplete">
           @include('laravel-crm::partials.form.hidden',[
               'name' => 'person_id',
               'attributes' => [
                    'wire:model' => 'person_id'        
                ]   
            ])
           <script type="text/javascript">
            let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\peopleWithDetails() !!}
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

    @push('livewire-js')
        <script>
            $(document).ready(function () {

                bindOrganisationAutocomplete();
                bindPersonAutocomplete();
                
                function bindOrganisationAutocomplete(){
                    $('input[name="organisation_name"]').autocomplete({
                        source: organisations,
                        value: 'value',
                        label: 'label',
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
                            Livewire.emit('invoiceOrganisationDeselected');
                        }else{
                            $(this).closest('.autocomplete').find(".autocomplete-new").hide()
                            $('.autocomplete-organisation').find('input,select').attr('disabled','disabled');
                        }
                        @this.set('organisation_id',$(this).val());
                    });

                    if($('input[name="organisation_id"]').val() == '' && $.trim($('input[name="organisation_id"]').closest('.autocomplete').find('input[name="organisation_name"]').val()) != ''){
                        $('input[name="organisation_id"]').closest('.autocomplete').find(".autocomplete-new").show();
                        $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
                    }

                    if($('input[name="organisation_name"]').closest('.autocomplete').find('input[name="organisation_id"]').val() == ''){
                        $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
                    }
                }

                function bindPersonAutocomplete(){

                    $('input[name="person_name"]').autocomplete({
                        source: people,
                        value: 'value',
                        label: 'label',
                        onSelectItem: function (item, element) {
                            @this.set('person_id',item.value);
                            @this.set('organisation_id', $(element).closest('form').find("input[name='organisation_id']").val());
                            @this.set('organisation_name', $(element).closest('form').find("input[name='organisation_name']").val());
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
