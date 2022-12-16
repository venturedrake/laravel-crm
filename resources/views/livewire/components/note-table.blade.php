<tr>
    <td>
        {{ $note->created_at->diffForHumans() }}
    </td>
    <td>{{ $note->content }}<br />
        @include('laravel-crm::notes.partials.note-model', ['note' => $note])
    </td>
    <td>
        @if($note->noted_at)
        {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}
        @endif
    </td>
    <td>{{ $note->createdByUser->name ?? null }}</td>
    <td class="disable-link text-right">
        @can('edit crm notes')
            {{--@if(! $note->completed_at)
                <a href="{{  route('laravel-crm.notes.complete',$note) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.complete')) }}</a>
            @endif--}}
            {{--<a href="{{  route('laravel-crm.notes.edit',$note) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>--}}
        @endcan
        @can('delete crm notes')
        <form action="{{ route('laravel-crm.notes.destroy',$note) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
            {{ method_field('DELETE') }}
            {{ csrf_field() }}
            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.note') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
        </form>
        @endcan
    </td>
</tr>

