<div id="notifyToast" class="toast show p-0 m-0 border border-success" style="position: absolute; top: 10px; right: 10px; z-index: 9999999999999"  data-delay="3000">
    <div class="toast-header alert-success">
        <span class="fa fa-check mr-0 rounded-circle p-2" aria-hidden="true"></span>
        <strong class="mr-auto">Success</strong>
        {{--<small>11 mins ago</small>--}}
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="toast-body alert alert-light mb-0" role="alert">
        A simple success alert—check it out!
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#notifyToast').toast('show')
        window.addEventListener('notifyToast', event => {
            $('#notifyToast').find('.toast-body').html(event.detail.message)
            $('#notifyToast').toast('show')
        });
    });
</script>