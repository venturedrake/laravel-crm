@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.quotes')) }} @include('laravel-crm::partials.filters', [
                'action' => route('laravel-crm.quotes.filter'),
                'model' => '\VentureDrake\LaravelCrm\Models\Quote'
            ])
        @endslot

        @slot('actions')
            @can('create crm quotes')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.quotes.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_quote')) }}</a></span>
            @endcan
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.title')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.sub_total')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.discount')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.tax')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.adjustment')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.total')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.issue_at')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.expire_at')) }}</th>
                <th scope="col">{{ ucwords(__('laravel-crm::lang.owner')) }}</th>
                <th scope="col" width="240"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($quotes as $quote)
               {{-- <tr class="has-link @if($quote->closed_status == 'won') table-success @elseif($quote->closed_status == 'lost') table-danger @endif" data-url="{{ url(route('laravel-crm.quotes.show',$quote)) }}">
                    <td>{{ $quote->title }}</td>
                    <td>@include('laravel-crm::partials.labels',[
                            'labels' => $quote->labels,
                            'limit' => 3
                        ])</td>
                    <td>{{ money($quote->amount, $quote->currency) }}</td>
                    <td>{{ $quote->organisation->name ?? null }}</td>
                    <td>{{ $quote->person->name ?? null }}</td>
                    <td>{{ ($quote->expected_close) ? $quote->expected_close->toFormattedDateString() : null }}</td>
                    <td>{{ $quote->ownerUser->name ?? null }}</td>
                    <td class="disable-link text-right">
                        @can('edit crm quotes')
                        @if(!$quote->closed_at)
                            <a href="{{  route('laravel-crm.quotes.won',$quote) }}" class="btn btn-success btn-sm">{{ ucfirst(__('laravel-crm::lang.won')) }}</a>
                            <a href="{{  route('laravel-crm.quotes.lost',$quote) }}" class="btn btn-danger btn-sm">{{ ucfirst(__('laravel-crm::lang.lost')) }}</a>
                        @else
                            <a href="{{  route('laravel-crm.quotes.reopen',$quote) }}" class="btn btn-outline-secondary btn-sm">{{ ucfirst(__('laravel-crm::lang.reopen')) }}</a>
                        @endif
                        @endcan
                        @can('view crm quotes')
                        <a href="{{  route('laravel-crm.quotes.show',$quote) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        @endcan
                        @can('edit crm quotes')    
                        <a href="{{  route('laravel-crm.quotes.edit',$quote) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        @endcan
                        @can('delete crm quotes')
                        <form action="{{ route('laravel-crm.quotes.destroy',$quote) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.quote') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                        @endcan
                    </td>
                </tr>--}}
            @endforeach
            </tbody>
        </table>

    @endcomponent

    @if($quotes instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $quotes->links() }}
        @endcomponent
    @endif

@endcomponent    