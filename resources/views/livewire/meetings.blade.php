<div class="meetings">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.meetings')) }}</h6>
    <hr />--}}
    @if($showForm)
        <form wire:submit.prevent="create" id="inputCreateForm">
            @include('laravel-crm::livewire.components.partials.meeting.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
            </div>
        </form>
        <hr/>
    @endif
    <ul class="list-unstyled">
        @foreach($meetings as $meeting)
            @livewire('meeting',[
                'meeting' => $meeting
            ], key($meeting->id))
        @endforeach
    </ul>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", ".meetings #inputCreateForm input[name='start_at']", function () {
                @this.set('start_at', $(this).val());
                });

                $(document).on("change", ".meetings #inputCreateForm input[name='finish_at']", function () {
                @this.set('finish_at', $(this).val());
                });

                $(document).on("change", '.meetings select[name="guests[]"]', function (e) {
                    var data = $('select[name="guests[]"]').select2("val");
                @this.set('guests', data);
                });

                window.addEventListener('meetingEditModeToggled', event => {
                    bindDateTimePicker();
                    bindSelect2();
                });

                window.addEventListener('meetingAddOn', event => {
                    $('.nav-activities li a#tab-meetings').tab('show')
                    bindDateTimePicker()
                    bindSelect2();
                });

                $('.nav-tabs a#tab-meetings').on('shown.bs.tab', function(event){
                    bindDateTimePicker()
                    bindSelect2();
                });

                window.addEventListener('meetingFieldsReset', event => {
                    bindDateTimePicker();
                    bindSelect2();
                });
            });

            function bindDateTimePicker(){
                $('.meetings input[name="start_at"]').datetimepicker({
                    timepicker:true,
                    format: 'Y/m/d H:i',
                });
                $('.meetings input[name="finish_at"]').datetimepicker({
                    timepicker:true,
                    format: 'Y/m/d H:i',
                });
            }

            function bindSelect2(){
                $('.meetings select[name="guests[]"]').select2();
            }
        </script>
    @endpush
</div>


