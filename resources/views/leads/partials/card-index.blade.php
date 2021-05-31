@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ ucfirst(__('laravel-crm::lang.leads')) }}
        @endslot

        @slot('actions')
            <span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.leads.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_lead')) }}</a></span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-table')

        <table class="table mb-0 card-table table-hover">
            <thead>
            <tr>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.title')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.labels')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.value')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.organization')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.contact_person')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                <th scope="col">{{ ucfirst(__('laravel-crm::lang.assigned_to')) }}</th>
                <th scope="col" width="210"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($leads as $lead)
                <tr class="has-link" data-url="{{ url(route('laravel-crm.leads.show',$lead)) }}">
                    <td>{{ $lead->title }}</td>
                    <td>@include('laravel-crm::partials.labels',[
                            'labels' => $lead->labels,
                            'limit' => 3
                        ])</td>
                    <td>{{ money($lead->amount, $lead->currency) }}</td>
                    <td>{{ $lead->organisation->name ?? $lead->organisation_name }}</td>
                    <td>{{ $lead->person->name ??  $lead->person_name }}</td>
                    <td>{{ $lead->created_at->diffForHumans() }}</td>
                    <td>{{ $lead->assignedToUser->name }}</td>
                    <td class="disable-link text-right">
                        <a href="{{  route('laravel-crm.leads.convert-to-deal',$lead) }}" class="btn btn-success btn-sm"> {{ ucfirst(__('laravel-crm::lang.convert')) }}</a>
                        <a href="{{  route('laravel-crm.leads.show',$lead) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                        <a href="{{  route('laravel-crm.leads.edit',$lead) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                        <form action="{{ route('laravel-crm.leads.destroy',$lead) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                            {{ method_field('DELETE') }}
                            {{ csrf_field() }}
                            <button class="btn btn-danger btn-sm" type="submit" data-model=" {{ ucfirst(__('laravel-crm::lang.lead')) }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
    @endcomponent

    @if($leads instanceof \Illuminate\Pagination\LengthAwarePaginator )
        @component('laravel-crm::components.card-footer')
            {{ $leads->links() }}
        @endcomponent
    @endif

@endcomponent    