require('./bootstrap');
require('bootstrap4-toggle/js/bootstrap4-toggle.min');
require('jquery-datetimepicker/build/jquery.datetimepicker.full')
require('bootstrap-4-autocomplete/dist/bootstrap-4-autocomplete')
require('chart.js/dist/chart.min')
require('../../bower_components/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js')
require('select2/dist/js/select2.min')
require('bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min')
require('trix/dist/trix')

import bsCustomFileInput from 'bs-custom-file-input'

const Swal = require('sweetalert2')

// Little bit of Jquery
const appJquery = function() {
    return {
        init: function () {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            bsCustomFileInput.init()

            $('[data-toggle="tooltip"]').tooltip()

            if($('meta[name=date_format]').length > 0){
                var dateFormat = $('meta[name=date_format]').attr('content');
            }else{
                var dateFormat = 'Y/m/d';
            }

            if($('meta[name=time_format]').length > 0){
                var timeFormat = $('meta[name=time_format]').attr('content');
            }else{
                var timeFormat = 'H:i';
            }

            $('input[name="birthday"],' +
                ' input[name="expected_close"],' +
                ' input[name="issue_at"],' +
                ' input[name="expire_at"],' +
                ' input[name="issue_date"],' +
                ' input[name="due_date"],' +
                ' input[name="delivery_expected"],' +
                ' input[name="delivered_on"]').datetimepicker({
                timepicker:false,
                format: dateFormat,
            });

            $('input[name="noted_at"], input[name="due_at"], input[name="start_at"], input[name="finish_at"]').datetimepicker({
                timepicker:true,
                format: dateFormat + ' H:i',
            });

            $( "tr.has-link > td:not(.disable-link)" ).on({
                click: function() {
                    window.location = $(this).closest('tr').data('url');
                },
                mouseover: function() {
                    $(this).css( 'cursor', 'pointer' );
                }
            });

            $('select[name="labels[]"]').select2({
               /* tags: true,*/
                tokenSeparators: [','],
                /*createTag: function (params) {
                    var term = params.term;

                    if (term === '') {
                        return null;
                    }

                    return {
                        id: 'new_label_' + term,
                        text: term,
                        newTag: true
                    }
                }*/
            });

            if(typeof products !== 'undefined'){
                if($('meta[name=dynamic_products]').length > 0){
                    var tags = JSON.parse($('meta[name=dynamic_products]').attr('content'));
                }else{
                    var tags = true;
                }
                
                $("td.bind-select2 select[name^='products']").select2({
                    data: products,
                    tags: tags
                });

                $("td.bind-select2 select[name^='invoiceLines']").select2({
                    data: products,
                    tags: tags
                });
            }

            $('#input_hex').colorpicker();

            $('form.form-delete-button > button[type="submit"]').on('click', function (e) {

                Swal.fire({
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false,
                    title: 'Are you sure you want to delete this ' + $(this).data('model') + '?',
                    showCancelButton: true,
                    focusConfirm: true,
                    confirmButtonText:
                        'Yes, Delete',
                    confirmButtonAriaLabel: 'Yes, Delete',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).closest('form').submit()
                    }
                })

                e.preventDefault();
            })

            if (typeof people !== 'undefined') {
                appJquery.bindPersonAutocomplete();
            }

            if (typeof organisations !== 'undefined') {
                appJquery.bindOrganisationAutocomplete();
            }

            $(document).on('click','.btn-action-add-deal-product', function(e) {
                $.get($(this).attr('href'), function(data){
                    $('#dealProducts').append(data);
                });
                e.preventDefault();
            });

            $(document).delegate("input[name^='item_name']", "focus", function() {

                $(this).autocomplete({
                    source: products,
                    onSelectItem: appJquery.onSelectProduct,
                    highlightClass: 'text-danger',
                    treshold: 2,
                });

            })

            $(document).on("change", "input[name^='item_price']", function() {
                var sum = $(this).closest('.row').find("input[name^='item_quantity']").val() * $(this).val();
                $(this).closest('.row').find("input[name^='item_amount']").val(sum);
            });

            $(document).on("change", "input[name^='item_quantity']", function() {
                var sum = $(this).closest('.row').find("input[name^='item_price']").val() * $(this).val();
                $(this).closest('.row').find("input[name^='item_amount']").val(sum);
            });

            $( "form[name='formSearch'] div.dropdown-menu > a").on({
                click: function() {
                    $(this).closest('form').attr('action', $(this).data('action'))
                    $(this).closest('form').find('.action-current').html($(this).text());
                },
            });

            if($('#createdLast14Days').length > 0){
                var chart = $('#createdLast14Days');
                var chartData = chart.data('chart');
                var chartDays = [];
                var chartLeads = [];
                var chartLeadsLabel = chart.data('label-leads');
                var chartDeals = [];
                var chartDealsLabel = chart.data('label-deals');
                Object.values(chartData).forEach(function (item, index) {
                    console.log('Testing...')
                    console.log(item, index);
                    chartDays.push(item.daily.date);
                    chartLeads.push(item.daily.leads);
                    chartDeals.push(item.daily.deals);
                });

                var myChart = new Chart(chart, {
                    type: 'bar',
                    data: {
                        labels: chartDays,
                        datasets: [{
                            label: chartLeadsLabel,
                            data: chartLeads,
                            borderWidth: 1,
                            backgroundColor: '#6c757d',
                            borderColor: "#373c40",
                        },
                            {
                                label: chartDealsLabel,
                                data: chartDeals,
                                borderWidth: 1,
                                backgroundColor: '#28a745',
                                borderColor: "#176529",
                            }]
                    },
                    options: {
                        responsive: false,
                        legend: {
                            display: false
                        },
                        scales: {
                            xAxes: [{
                                gridLines: {
                                    display: false
                                }
                            }],
                            yAxes: [{
                                ticks: {
                                    stepSize: 1
                                },
                                gridLines: {
                                    display: false
                                }
                            }]
                        }
                    }
                });
            }

            // bootstrap-duallistbox
            $("select[name^='team_users']").bootstrapDualListbox({
                nonSelectedListLabel: 'Not on Team',
                selectedListLabel: 'On Team',
                moveOnSelect: false,
                infoText: false,
                iconsPrefix: 'fa'
            });

            $("select[name^='user_teams']").bootstrapDualListbox({
                nonSelectedListLabel: 'Not on Team',
                selectedListLabel: 'On Team',
                moveOnSelect: false,
                infoText: false,
                iconsPrefix: 'fa'
            });

            $("select[name^='field_models']").bootstrapDualListbox({
                nonSelectedListLabel: 'Not Attached',
                selectedListLabel: 'Attached To',
                moveOnSelect: false,
                infoText: false,
                iconsPrefix: 'fa'
            });

            // bootstrap-multiselect
            $('select[name="user_owner_id[]"]').multiselect({
                buttonText: function(options, select) {
                    return 'Owner';
                },
                buttonTitle: function(options, select) {
                    var labels = [];
                    options.each(function () {
                        labels.push($(this).text());
                    });
                    return labels.join(' - ');
                },
                includeSelectAllOption: true,
                selectAllJustVisible: false,
                selectAllName: 'select-all-owner',
                enableFiltering: true,
                enableCaseInsensitiveFiltering: true,
                maxHeight: 500
            });

            $('select[name="label_id[]"]').multiselect({
                buttonText: function(options, select) {
                    return 'Label';
                },
                buttonTitle: function(options, select) {
                    var labels = [];
                    options.each(function () {
                        labels.push($(this).text());
                    });
                    return labels.join(' - ');
                },
                includeSelectAllOption: true,
                selectAllJustVisible: false,
                selectAllName: 'select-all-label',
                enableFiltering: true,
                enableCaseInsensitiveFiltering: true,
                maxHeight: 500
            });

            $('form button#clear-filter').on('click', function() {
               $(this).closest('form').find('.col').removeClass('filter-active')
               $(this).closest('form').find('select').each(function( index ) {
                   $(this).find('option').each(function( index ) {
                       $(this).prop('selected', true)
                   });
                   $(this).multiselect('refresh');
                });
            });
        },

        bindPersonAutocomplete: function (){

            $('input[name="person_name"]').autocomplete({
                source: people,
                onSelectItem: appJquery.onSelectPerson,
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

            if($('input[name="person_name"]').closest('.autocomplete').find('input[name="person_id"]').val() == ''){
                $('.autocomplete-person').find('input,select').removeAttr('disabled');
            }

        },

        bindOrganisationAutocomplete: function (){
            $('input[name="organisation_name"]').autocomplete({
                source: organisations,
                onSelectItem: appJquery.onSelectOrganisation,
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

            if($('input[name="organisation_name"]').closest('.autocomplete').find('input[name="organisation_id"]').val() == ''){
                $('.autocomplete-organisation').find('input,select').removeAttr('disabled');
            }
        },

        onSelectPerson: function (item, element) {
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

        onSelectOrganisation: function (item, element) {
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

        onSelectProduct: function (item, element) {
            $(element).closest('.autocomplete').find("input[name^='item_product_id']").val(item.value).trigger('change');
            var dealProduct = $(element).closest('.deal-product-row');

            $.ajax({
                url: "/crm/products/" +  item.value + "/autocomplete",
                cache: false
            }).done(function( data ) {
                $(dealProduct).find("input[name^='item_price']").val(data.price);
                $(dealProduct).find("input[name^='item_amount']").val(data.price * $(dealProduct).find("input[name^='item_quantity']").val())
            });
        },
    }
}();

$(document).ready(function() {
    appJquery.init();
});
