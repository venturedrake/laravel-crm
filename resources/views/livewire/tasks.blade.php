<div class="tasks">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.tasks')) }}</h6>
    <hr />--}}
    @if($showForm)
        @if(! $pinned)
        <form wire:submit.prevent="create" id="inputCreateForm">
            @include('laravel-crm::livewire.components.partials.task.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
            </div>
        </form>
        <hr/>
        @endif
    @endif
    <ul class="list-unstyled">
        @foreach($tasks as $task)
            @livewire('task',[
                'task' => $task
            ], key($task->id))
        @endforeach
    </ul>
    @if(! $pinned)
        @push('livewire-js')
            <script>
                $(document).ready(function () {
                    $(document).on("change", "#inputCreateForm input[name='taskd_at']", function () {
                        @this.set('taskd_at', $(this).val());
                    });
                });
            </script>
        @endpush
        @push('livewire-js')
            <script>
                $(document).ready(function () {
                    window.addEventListener('taskEditModeToggled', event => {
                        bsCustomFileInput.init()
                        $('.nav-activities li a#tab-tasks').tab('show')
                        $('input[name="taskd_at"]').datetimepicker({
                            timepicker:true,
                            format: 'Y/m/d H:i',
                        });
                    });
                });
            </script>
        @endpush
    @endif
</div>


