@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.tasks')) }}
        @endslot
    
        @slot('actions')
            {{--@can('create crm tasks')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.tasks.create')) }}"><span class="fa fa-plus"></span> {{ ucfirst(__('laravel-crm::lang.add_task')) }}</a></span>
            @endcan--}}
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.status')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.task')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.description')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.due')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created_by')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.assigned_to')) }}</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($tasks as $task)
                @livewire('task',[
                    'task' => $task,
                    'view' => 'task-table'
                ], key($task->id))
            @endforeach
            </tbody>
        </table>
        
    @endcomponent
    
    @if($tasks instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $tasks->links() }}
        @endcomponent
    @endif
    
@endcomponent    