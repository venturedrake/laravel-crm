@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.organizations')) }} @include('laravel-crm::partials.filters', ['action' => route('laravel-crm.organisations.filter')])
        @endslot

        @slot('actions')
            @can('create crm organisations')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.organisations.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_organization')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.name')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.contact')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.open_deals')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.lost_deals')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.won_deals')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.next_activity')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="150"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($organisations as $organisation)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.organisations.show',$organisation)) }}">
                    <td>{{ $organisation->name }}</td>
                    <td>@include('laravel-crm::partials.labels',[
                            'labels' => $organisation->labels,
                            'limit' => 3
                        ])</td>
                    <td></td>
                    <td>{{ $organisation->deals->whereNull('closed_at')->count() }}</td>
                    <td>{{ $organisation->deals->where('closed_status', 'lost')->count() }}</td>
                    <td>{{ $organisation->deals->where('closed_status', 'won')->count() }}</td>
                    <td></td>
                    <td>{{ $organisation->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('view crm organisations')
                        <a href="{{  route('laravel-crm.organisations.show',$organisation) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm organisations')
                        <a href="{{  route('laravel-crm.organisations.edit',$organisation) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm organisations')    
                        <form action="{{ route('laravel-crm.organisations.destroy',$organisation) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.organization') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan    
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
    @endcomponent

    @if($organisations instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $organisations->links() }}
        @endcomponent
    @endif

@endcomponent    