@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.clients')) }}
        @endslot

        @slot('actions')
            @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.clients.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Client'
            ])
            @can('create crm clients')
                <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.clients.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_client')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">@sortablelink('name', ucwords(__('laravel-crm::lang.name')))</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="150"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($clients as $client)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.clients.show',$client)) }}">
                    <td>{{ $client->name }}</td>
                    <td>@include('laravel-crm::partials.labels',[
                            'labels' => $client->labels,
                            'limit' => 3
                        ])</td>
                    <td>{{ $client->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('create crm leads')
                            <a href="{{ route('laravel-crm.leads.create', ['model' => 'client', 'id' => $client->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-crosshairs" aria-hidden="true"></span></a>
                        @endcan
                        @can('create crm deals')
                            <a href="{{ route('laravel-crm.deals.create', ['model' => 'client', 'id' => $client->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-dollar" aria-hidden="true"></span></a>
                        @endcan
                        @can('create crm quotes')
                            <a href="{{ route('laravel-crm.quotes.create', ['model' => 'client', 'id' => $client->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-file-text" aria-hidden="true"></span></a>
                        @endcan
                        @can('create crm orders')
                            <a href="{{ route('laravel-crm.orders.create', ['model' => 'client', 'id' => $client->id]) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-arrow-right" aria-hidden="true"></span> <span class="fa fa-shopping-cart" aria-hidden="true"></span></a>
                        @endcan    
                        @can('view crm clients')
                            <a href="{{  route('laravel-crm.clients.show',$client) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm clients')
                            <a href="{{  route('laravel-crm.clients.edit',$client) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm clients')    
                        <form action="{{ route('laravel-crm.clients.destroy',$client) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.client') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
    @endcomponent

    @if($clients instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $clients->appends(request()->except('page'))->links() }}
        @endcomponent
    @endif

@endcomponent    