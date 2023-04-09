@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.people')) }}
        @endslot
    
        @slot('actions')
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.people.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Person'
            ])
            @can('create crm people')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.people.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_person')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')
        
        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">@sortablelink('first_name', ucwords(__('laravel-crm::lang.name')))</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">@sortablelink('organisation.name', ucwords(__('laravel-crm::lang.organization')))</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.email')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.phone')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.open_deals')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.lost_deals')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.won_deals')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.next_activity')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="150"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($people as $person)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.people.show',$person)) }}">
                    <td>{{ $person->name }}</td>
                    <td>@include('laravel-crm::partials.labels',[
                            'labels' => $person->labels,
                            'limit' => 3
                        ])</td>
                    <td>{{ $person->organisation->name ?? null }}</td>
                    <td>{{ $person->getPrimaryEmail()->address ?? null }}</td>
                    <td>{{ $person->getPrimaryPhone()->number ?? null }}</td>
                    <td>{{ $person->deals->whereNull('closed_at')->count() }}</td>
                    <td>{{ $person->deals->where('closed_status', 'lost')->count() }}</td>
                    <td>{{ $person->deals->where('closed_status', 'won')->count() }}</td>
                    <td></td>
                    <td>{{ $person->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('create crm leads')
                            <a href="{{ route('laravel-crm.leads.create', ['model' => 'person', 'id' => $person->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-crosshairs" aria-hidden="true"></span></a>
                        @endcan
                        @can('create crm deals')
                            <a href="{{ route('laravel-crm.deals.create', ['model' => 'person', 'id' => $person->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-dollar" aria-hidden="true"></span></a>
                        @endcan
                        @can('create crm quotes')
                            <a href="{{ route('laravel-crm.quotes.create', ['model' => 'person', 'id' => $person->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-file-text" aria-hidden="true"></span></a>
                        @endcan
                        @can('create crm orders')
                            <a href="{{ route('laravel-crm.orders.create', ['model' => 'person', 'id' => $person->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-shopping-cart" aria-hidden="true"></span></a>
                        @endcan
                        @can('view crm people')
                        <a href="{{  route('laravel-crm.people.show',$person) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm people')
                        <a href="{{  route('laravel-crm.people.edit',$person) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm people')    
                        <form action="{{ route('laravel-crm.people.destroy',$person) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.person') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan    
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
    @endcomponent
    
    @if($people instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $people->appends(request()->except('page'))->links() }}
        @endcomponent
    @endif
    
@endcomponent    