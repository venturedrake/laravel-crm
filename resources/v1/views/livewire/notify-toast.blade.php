<div>
    <div id="notifyToast" class="toast p-0 m-0 border border-{{ $level }} w-75" style="position: fixed; top: 10px; right: 18px;"  data-delay="3000" data-autohide="true">
        <div class="toast-header alert-{{ $level }}">
            <span class="fa fa-check mr-0 rounded-circle p-2" aria-hidden="true"></span>
            <strong class="mr-auto">{{ ucwords($level) }}</strong>
            {{--<small>11 mins ago</small>--}}
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body alert alert-light mb-0" role="alert">
            {{ $message }}
        </div> 
    </div>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                window.addEventListener('notifyToast', event => {
                    $('#notifyToast').toast('show')
                    @this.set('level',event.detail.level);
                    @this.set('message',event.detail.message);
                });
            });
        </script>
    @endpush
</div>
