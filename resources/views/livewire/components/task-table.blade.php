<tr>
    <td>{{ $task->created_at->diffForHumans() }}</td>
    <td>
        @include('laravel-crm::livewire.components.partials.task.status', ['task' => $task])
    </td>
    <td>{{ $task->name }}<br />
        @include('laravel-crm::tasks.partials.task-model', ['task' => $task])
    </td>
    <td>{{ $task->description }}</td>
    <td>
        @if($task->due_at)
            {{ $task->due_at->format('h:i A') }} on {{ $task->due_at->toFormattedDateString() }}
        @endif
    </td>
    <td>{{ $task->createdByUser->name ?? null }}</td>
    <td>{{ $task->assignedToUser->name ?? null }}</td>
    <td class="disable-link text-right">
        @can('edit crm tasks')
            @if(! $task->completed_at)
                <a href="{{  route('laravel-crm.tasks.complete',$task) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.complete')) }}</a>
            @endif
            {{--<a href="{{  route('laravel-crm.tasks.edit',$task) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>--}}
        @endcan
        @can('delete crm tasks')
        <form action="{{ route('laravel-crm.tasks.destroy',$task) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
            {{ method_field('DELETE') }}
            {{ csrf_field() }}
            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.task') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
        </form>
        @endcan
    </td>
</tr>

