@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.notes')) }}
        @endslot
    
        @slot('actions')
            {{--@can('create crm notes')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.notes.create')) }}"><span class="fa fa-plus"></span> {{ ucfirst(__('laravel-crm::lang.add_note')) }}</a></span>
            @endcan--}}
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.note')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.noted_at')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created_by')) }}</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($notes as $note)
               {{-- @livewire('note',[
                    'note' => $note,
                    'view' => 'note-table'
                ], key($note->id))--}}
            @endforeach
            </tbody>
        </table>
        
    @endcomponent
    
    @if($notes instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $notes->links() }}
        @endcomponent
    @endif
    
@endcomponent    