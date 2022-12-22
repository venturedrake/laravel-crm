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
                $(document).on("change", "#inputCreateForm input[name='start_at']", function () {
                    @this.set('start_at', $(this).val());
                });

                $(document).on("change", "#inputCreateForm input[name='finish_at']", function () {
                    @this.set('finish_at', $(this).val());
                });

                window.addEventListener('meetingEditModeToggled', event => {
                    $('input[name="start_at"]').datetimepicker({
                     timepicker:true,
                     format: 'Y/m/d H:i',
                    });
                    $('input[name="finish_at"]').datetimepicker({
                        timepicker:true,
                        format: 'Y/m/d H:i',
                    });
                });

                window.addEventListener('meetingAddOn', event => {
                    $('.nav-activities li a#tab-meetings').tab('show')
                    $('input[name="start_at"]').datetimepicker({
                        timepicker:true,
                        format: 'Y/m/d H:i',
                    });
                    $('input[name="finish_at"]').datetimepicker({
                        timepicker:true,
                        format: 'Y/m/d H:i',
                    });
                });
            });
        </script>
    @endpush
</div>


