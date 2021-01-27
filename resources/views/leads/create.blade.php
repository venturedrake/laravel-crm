@extends('laravel-crm::layouts.app')

@section('content')

    <form method="POST" action="{{ url(route('laravel-crm.leads.store')) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title float-left m-0">Create lead</h3> <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.leads.index')) }}"><span class="fa fa-angle-double-left"></span> Back to leads</a></span></div>
            <div class="card-body">

                <div class="row">
                    <div class="col-sm-6">
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'person_name',
                           'label' => 'Contact person',
                           'prepend' => '<span class="fa fa-user" aria-hidden="true"></span>',
                       ])
                        @include('laravel-crm::partials.form.text',[
                            'name' => 'organisation_name',
                            'label' => 'Organisation',
                            'prepend' => '<span class="fa fa-building" aria-hidden="true"></span>'
                        ])
                        @include('laravel-crm::partials.form.text',[
                            'name' => 'title',
                            'label' => 'Title'
                        ])
                        @include('laravel-crm::partials.form.textarea',[
                             'name' => 'description',
                             'label' => 'Description',
                             'rows' => 5
                        ])

                        <div class="row">
                            <div class="col-sm-6">
                                @include('laravel-crm::partials.form.text',[
                                      'name' => 'amount',
                                      'label' => 'Value',
                                      'prepend' => '<span class="fa fa-dollar" aria-hidden="true"></span>'
                                  ])
                            </div>
                            <div class="col-sm-6">
                                @include('laravel-crm::partials.form.select',[
                                    'name' => 'currency',
                                    'label' => 'Currency',
                                    'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies(),
                                    'default' => 'USD'
                                ])
                            </div>
                        </div>
                        @include('laravel-crm::partials.form.select',[
                                 'name' => 'user_assigned_id',
                                 'label' => 'Owner',
                                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users(false),
                                 'selected' => old('user_assigned_id') ?? auth()->user()->id
                              ])
                    </div>
                    <div class="col-sm-6">
                        <h6><span class="fa fa-user" aria-hidden="true"></span> Person</h6>
                        <hr />
                        <div class="row">
                            <div class="col-sm-6">
                                @include('laravel-crm::partials.form.text',[
                                 'name' => 'phone',
                                 'label' => 'Phone'
                              ])
                            </div>
                            <div class="col-sm-6">
                                @include('laravel-crm::partials.form.select',[
                                 'name' => 'phone_type',
                                 'label' => 'Type',
                                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\phoneTypes(),
                                 'selected' => old('phone_type') ?? 'work'
                              ])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                @include('laravel-crm::partials.form.text',[
                                 'name' => 'email',
                                 'label' => 'Email'
                              ])
                            </div>
                            <div class="col-sm-6">
                                @include('laravel-crm::partials.form.select',[
                                 'name' => 'email_type',
                                 'label' => 'Type',
                                 'options' => \VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\emailTypes(),
                                 'selected' => old('email_type') ?? 'work'
                              ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.leads.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </div>
    </form>

@endsection