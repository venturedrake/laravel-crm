@component('laravel-crm::components.card')

    @component('laravel-crm::components.card-header')

        @slot('title')
            {{ $person->name }} <small>@include('laravel-crm::partials.labels',[
                            'labels' => $person->labels
                    ])</small>
        @endslot

        @slot('actions')
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.people.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_people')) }}</a> | 
                @can('create crm deals')
                <a href="{{ url(route('laravel-crm.deals.create',['model' => 'person', 'id' => $person->id])) }}" alt="Add deal" class="btn btn-success btn-sm"><span class="fa fa-plus" aria-hidden="true"></span> {{ ucfirst(__('laravel-crm::lang.add_new_deal')) }}</a>
                @endcan
                @include('laravel-crm::partials.navs.activities') |
                @can('edit crm people')
                <a href="{{ url(route('laravel-crm.people.edit', $person)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm people')
                <form action="{{ route('laravel-crm.people.destroy',$person) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.person') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan    
            </span>
        @endslot

    @endcomponent

    @component('laravel-crm::components.card-body')
        
        <div class="row">
            <div class="col-sm-6 border-right">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.first_name')) }}</dt>
                    <dd class="col-sm-9">{{ $person->first_name }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.middle_name')) }}</dt>
                    <dd class="col-sm-9">{{ $person->middle_name }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.last_name')) }}</dt>
                    <dd class="col-sm-9">{{ $person->last_name }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.gender')) }}</dt>
                    <dd class="col-sm-9">{{ ucfirst($person->gender) }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.birthday')) }}</dt>
                    <dd class="col-sm-9">{{ $person->birthday }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.description')) }}</dt>
                    <dd class="col-sm-9">{{ $person->description }}</dd>
                    @foreach($phones as $phone)
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.phone')) }}</dt>
                        <dd class="col-sm-9">
                            <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }}{{ ($phone->primary) ? ', Primary' : null }})
                        </dd>
                    @endforeach
                    @foreach($emails as $email)
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.email')) }}</dt>
                        <dd class="col-sm-9">
                            <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }}{{ ($email->primary) ? ', Primary' : null }})
                        </dd>
                    @endforeach
                    @foreach($addresses as $address)
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.address')) }}</dt>
                        <dd class="col-sm-9">
                            {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) }} {{ ($address->primary) ? '(Primary)' : null }}
                        </dd>
                    @endforeach
                </dl>
                <h6 class="mt-4 text-uppercase"> {{ ucfirst(__('laravel-crm::lang.organization')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right"><span class="fa fa-building" aria-hidden="true"></span></dt>
                    <dd class="col-sm-9">{{ $organisation->name ?? null }}</dd>
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.address')) }}</dt>
                    <dd class="col-sm-9">{{ ($organisation_address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($organisation_address) : null }}</dd>
                </dl>
                @can('view crm deals')
                <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.deals')) }} ({{ $person->deals->count() }})</span>@can('create crm deals')<span class="float-right"><a href="{{ url(route('laravel-crm.deals.create',['model' => 'person', 'id' => $person->id])) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span>@endcan</h6>
                <hr />
                @foreach($person->deals as $deal)
                    <p>{{ $deal->title }}<br />
                        <small>{{ money($deal->amount, $deal->currency) }}</small></p>
                @endforeach
                @endcan
                <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.owner')) }}</h6>
                <hr />
                <dl class="row">
                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.name')) }}</dt>
                    <dd class="col-sm-9">{{ $person->ownerUser->name }}</dd>
                </dl>
            </div>
            <div class="col-sm-6">
                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.notes')) }}</h6>
                <hr />
                ...
                <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.files')) }}</h6>
                <hr />
                ...
                <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.activities')) }}</h6>
                <hr />
                ...
            </div>
        </div>
        
    @endcomponent    

@endcomponent    