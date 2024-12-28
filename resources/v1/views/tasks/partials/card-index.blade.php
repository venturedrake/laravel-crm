@component('laravel-crm::components.card')

    <div class="card-header">
        @include('laravel-crm::layouts.partials.nav-activities')
    </div>

    <div class="card-body p-0">
        <div class="tab-content">
            <div class="tab-pane active" id="roles" role="tabpanel">
                <h3 class="m-3"> {{ ucfirst(__('laravel-crm::lang.tasks')) }}</h3>
                <div class="table-responsive">
                    <table class="table mb-0 card-table table-hover">
                        <thead>
                        <tr>
                            <th scope="col">{{ ucwords(__('laravel-crm::lang.created')) }}</th>
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
                        @if($tasks && $tasks->count() > 0)
                            @foreach($tasks as $task)
                                @livewire('task',[
                                'task' => $task,
                                'view' => 'task-table'
                                ], key($task->id))
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7">
                                    {{ ucfirst(__('laravel-crm::lang.no_tasks')) }}
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($tasks instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $tasks->links() }}
        @endcomponent
    @endif

@endcomponent
