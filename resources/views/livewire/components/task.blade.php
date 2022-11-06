<li class="media">
    <div class="card w-100 mb-2">
        <div class="card-body">
            {{--<img src="..." class="mr-3" alt="...">--}}
            <div class="media-body">
                <h5 class="mt-0 mb-1">{{ $task->name }} @include('laravel-crm::livewire.components.partials.task.actions', ['task' => $task])</h5>
                @include('laravel-crm::livewire.components.partials.task.content', ['task' => $task])
            </div>
        </div>
    </div>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", "input[name='due_at']", function () {
                    @this.set('due_at', $(this).val());
                });
            });
        </script>
    @endpush
</li>

