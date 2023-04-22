<div class="calls">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.calls')) }}</h6>
    <hr />--}}
    @if($showForm)
        <form wire:submit.prevent="create" id="inputCreateForm">
            @include('laravel-crm::livewire.components.partials.call.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
            </div>
        </form>
        <hr/>
    @endif
    <ul class="list-unstyled">
        @foreach($calls as $call)
            @livewire('call',[
                'call' => $call
            ], key($call->id))
        @endforeach
    </ul>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", ".calls #inputCreateForm input[name='start_at']", function () {
                    @this.set('start_at', $(this).val());
                });

                $(document).on("change", ".calls #inputCreateForm input[name='finish_at']", function () {
                    @this.set('finish_at', $(this).val());
                });

                $(document).on("change", '.calls select[name="guests[]"]', function (e) {
                    var data = $('select[name="guests[]"]').select2("val");
                    @this.set('guests', data);
                });

                window.addEventListener('callEditModeToggled', event => {
                    bindDateTimePicker_Call();
                    bindSelect2_Call();
                });

                window.addEventListener('callAddOn', event => {
                    $('.nav-activities li a#tab-calls').tab('show')
                    bindDateTimePicker_Call()
                    bindSelect2_Call();
                });

                $('.nav-tabs a#tab-calls').on('shown.bs.tab', function(event){
                    bindDateTimePicker_Call()
                    bindSelect2_Call();
                });

                window.addEventListener('callFieldsReset', event => {
                    bindDateTimePicker_Call();
                    bindSelect2_Call();
                });
            });
            
            function bindDateTimePicker_Call(){
                $('.calls input[name="start_at"]').datetimepicker({
                    timepicker:true,
                    format: '{{ $dateFormat }} H:i',
                });
                $('.calls input[name="finish_at"]').datetimepicker({
                    timepicker:true,
                    format: '{{ $dateFormat }} H:i',
                });
            }
            
            function bindSelect2_Call(){
                $('.calls select[name="guests[]"]').select2();
            }
        </script>
    @endpush
</div>


