@component('laravel-crm::components.card')

    <div class="card-header">
        @include('laravel-crm::layouts.partials.nav-activities')
    </div>
    
    <div class="card-body p-0">
        <div class="tab-content">
            <div class="tab-pane active" id="roles" role="tabpanel">
                <h3 class="m-3"> {{ ucfirst(__('laravel-crm::lang.notes')) }}</h3>
                <div class="table-responsive">
                    <table class="table mb-0 card-table table-hover">
                        <thead>
                        <tr>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.note')) }}</th>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.noted_at')) }}</th>
                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.created_by')) }}</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($notes && $notes->count() > 0)
                            @foreach($notes as $note)
                                @livewire('note',[
                                'note' => $note,
                                'view' => 'note-table'
                                ], key($note->id))
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7">
                                    {{ ucfirst(__('laravel-crm::lang.no_notes')) }}
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @if($notes instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $notes->links() }}
        @endcomponent
    @endif

@endcomponent
