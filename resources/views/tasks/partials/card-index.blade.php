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
                <tr>
                    <td>
                        @include('laravel-crm::livewire.components.partials.task.status', ['task' => $task])
                    </td>
                    <td>{{ $task->name }}</td>
                    <td>{{ $task->description }}</td>
                    <td>
                        @if($task->due_at)
                            {{ $task->due_at->format('h:i A') }} on {{ $task->due_at->toFormattedDateString() }}
                        @endif
                    </td>
                    <td>{{ $task->createdByUser->name ?? null }}</td>
                    <td>{{ $task->assignedToUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        {{--@can('view crm products')
                        <a href="{{  route('laravel-crm.products.show',$product) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm products')
                        <a href="{{  route('laravel-crm.products.edit',$product) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm products')    
                        <form action="{{ route('laravel-crm.products.destroy',$product) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.product') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan --}}   
                    </td>
                </tr>
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