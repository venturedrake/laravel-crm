@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.teams')) }}
        @endslot

        @slot('actions')
            @can('create crm teams')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.teams.create')) }}"><span class="fa fa-plus"></span> {{ ucfirst(__('laravel-crm::lang.add_team')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.name')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.created_by')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.created')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.updated')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.users')) }}</th>
                <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($teams as $team)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.teams.show',$team)) }}">
                    <td>{{ $team->name }}</td>
                    <td>{{ $team->userCreated->name }}</td>
                    <td>{{ $team->created_at->format($dateFormat) }}</td>
                    <td>{{ $team->updated_at->format($dateFormat) }}</td>
                    <td>{{ $team->users->count() }}</td>
                    <td class="disable-link text-right">
                        @can('view crm teams')
                        <a href="{{  route('laravel-crm.teams.show',$team) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm teams')
                        <a href="{{  route('laravel-crm.teams.edit',$team) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm teams')
                        <form action="{{ route('laravel-crm.teams.destroy',$team) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.team') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
    @endcomponent

    @if($teams instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $teams->links() }}
        @endcomponent
    @endif

@endcomponent    