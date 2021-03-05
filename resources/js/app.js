require('./bootstrap');
require('bootstrap4-toggle/js/bootstrap4-toggle.min');
require('jquery-datetimepicker/build/jquery.datetimepicker.full')
require('bootstrap-4-autocomplete/dist/bootstrap-4-autocomplete')

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
            
            $('input[name="birthday"], input[name="expected"]').datetimepicker({
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
                    onSelectItem: appJquery.onSelectItem,
                    highlightClass: 'text-danger',
                    treshold: 2,
                });
            }
            
            if (typeof organisations !== 'undefined') {
                $('input[name="organisation_name"]').autocomplete({
                    source: organisations,
                    onSelectItem: appJquery.onSelectItem,
                    highlightClass: 'text-danger',
                    treshold: 2,
                });
            }
            
        },

        onSelectItem: function (item, element) {
            $(element).closest('.autocomplete').find('input[name="person_id"],input[name="organisation_id"]').val(item.value);
        }
        
    }
}();

$(document).ready(function() {
    appJquery.init();
});