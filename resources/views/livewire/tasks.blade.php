<div class="tasks">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.tasks')) }}</h6>
    <hr />--}}
    @if($showForm)
        <form wire:submit.prevent="create" id="inputCreateForm">
            @include('laravel-crm::livewire.components.partials.task.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
            </div>
        </form>
        <hr/>
    @endif
    <ul class="list-unstyled">
        @foreach($tasks as $task)
            @livewire('task',[
                'task' => $task
            ], key($task->id))
        @endforeach
    </ul>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", "#inputCreateForm input[name='due_at']", function () {
                    @this.set('due_at', $(this).val());
                });

                window.addEventListener('taskEditModeToggled', event => {
                    bsCustomFileInput.init()
                    $('input[name="due_at"]').datetimepicker({
                     timepicker:true,
                     format: '{{ $dateFormat }} H:i',
                    });
                });

                window.addEventListener('taskAddOn', event => {
                    $('.nav-activities li a#tab-tasks').tab('show')
                    $('input[name="due_at"]').datetimepicker({
                        timepicker:true,
                        format: '{{ $dateFormat }} H:i',
                    });
                });
            });
        </script>
    @endpush
</div>


