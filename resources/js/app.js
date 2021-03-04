require('./bootstrap');
require('bootstrap4-toggle/js/bootstrap4-toggle.min');
require('jquery-datetimepicker/build/jquery.datetimepicker.full')

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
            
        },
        
    }
}();

$(document).ready(function() {
    appJquery.init();
});