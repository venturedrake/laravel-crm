@extends('laravel-crm::layouts.app')

@section('content')

    <div class="container-content">
        <div class="row">
            @hasleadsenabled
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.leads')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalLeadsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_leads')) }}</small>
                    </div>
                </div>
            </div>
            @endhasleadsenabled
            @hasdealsenabled
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.deals')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalDealsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_deals')) }}</small>
                    </div>
                </div>
            </div>
            @endhasdealsenabled
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.people')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalPeopleCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_people')) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.organizations')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalOrganisationsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_organizations')) }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title m-0">{{ ucfirst(__('laravel-crm::lang.created_last_14_days')) }}</h4>
                </div>
                <div class="card-body">
                    <canvas id="createdLast14Days" style="height:500px; width:100%" data-chart="{{ $createdLast14Days }}" data-label-leads="{{ ucfirst(__('laravel-crm::lang.leads')) }}" data-label-deals="{{ ucfirst(__('laravel-crm::lang.deals')) }}"></canvas>
                </div>
            </div>
        </div>
        <div class="col-sm mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title m-0">{{ ucfirst(__('laravel-crm::lang.users_online')) }}</h4>
                </div>
                <div class="card-body">
                    @foreach($usersOnline as $user)
                        <div class="media {{ (!$loop->last) ? 'mb-3' : null }}">
                            <span class="fa fa-user fa-2x mr-3 border rounded-circle p-2" aria-hidden="true"></span>
                            <div class="media-body">
                                <h4 class="mt-1 mb-0">{{ $user->name }}</h4>
                                <p class="mb-0">{{  \Carbon\Carbon::parse($user->last_online_at)->diffForHumans() }}.</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
@endsection