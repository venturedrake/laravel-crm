@extends('laravel-crm::layouts.app')

@section('content')

    <div class="container-content">
        <div class="row">
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">Leads</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalLeadsCount ?? 0 }}</h2>
                        <small>Total leads</small>
                    </div>
                </div>
            </div>
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">Deals</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalDealsCount ?? 0 }}</h2>
                        <small>Total deals</small>
                    </div>
                </div>
            </div>
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">People</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalPeopleCount ?? 0 }}</h2>
                        <small>Total people</small>
                    </div>
                </div>
            </div>
            <div class="col-sm mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">Organisations</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalOrganisationsCount ?? 0 }}</h2>
                        <small>Total organisations</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title m-0">Created last 14 days</h4>
                </div>
                <div class="card-body">
                    <canvas id="createdLast14Days" style="height:500px; width:100%" data-chart="{{ $createdLast14Days }}"></canvas>
                </div>
            </div>
        </div>
        <div class="col-sm mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title m-0">Users online</h4>
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