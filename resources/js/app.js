require('./bootstrap');
require('bootstrap4-toggle/js/bootstrap4-toggle.min');
require('jquery-datetimepicker/build/jquery.datetimepicker.full')
require('bootstrap-4-autocomplete/dist/bootstrap-4-autocomplete')
require('chart.js/dist/chart.min')

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

            $('[data-toggle="tooltip"]').tooltip()
            
            $('input[name="birthday"], input[name="expected_close"]').datetimepicker({
                timepicker:false,
                format: 'Y/m/d',
            });

            $( "tr.has-link > td:not('.disable-link')" ).on({
                click: function() {
                    window.location = $(this).closest('tr').data('url');
                },
                mouseover: function() {
                    $(this).css( 'cursor', 'pointer' );
                }
            });

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
                    if($(this).val() == ''){
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

            }
            
            if (typeof organisations !== 'undefined') {
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
                    if($(this).val() == ''){
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
            }

            $( "form[name='formSearch'] div.dropdown-menu > a").on({
                click: function() {
                    $(this).closest('form').attr('action', $(this).data('action'))
                    $(this).closest('form').find('.action-current').html($(this).text());
                },
            });

           
            var chart = $('#createdLast14Days').getContext('2d');
            var chartData = chart.data('chart');
            var chartDays = [];
            var chartLeads = [];
            var chartDeals = [];
            Object.values(chartData).forEach(function (item, index) {
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
                        label: 'Leads',
                        data: chartLeads,
                        borderWidth: 1,
                        backgroundColor: '#6c757d',
                        borderColor: "#373c40",
                    },
                    {
                        label: 'Deals',
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

        }
        
    }
}();

$(document).ready(function() {
    appJquery.init();
});