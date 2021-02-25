@extends('laravel-crm::layouts.app')

@section('content')

    <form method="POST" action="{{ url(route('laravel-crm.users.sendinvite')) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title float-left m-0">Create user</h3> <span class="float-right"><a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.users.index')) }}"><span class="fa fa-angle-double-left"></span> Back to users</a></span></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'email',
                           'label' => 'Email',
                           'value' => old('email')
                         ])
                        @include('laravel-crm::partials.form.text',[
                           'name' => 'subject',
                           'label' => 'Subject',
                           'value' => old('subject', 'Invitation to join Laravel CRM'),
                         ])
                        @include('laravel-crm::partials.form.textarea',[
                          'name' => 'message',
                          'label' => 'Message',
                          'rows' => 5,
                          'value' => old('message') 
                       ])
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ url(route('laravel-crm.users.index')) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Send Invite</button>
            </div>
        </div>
    </form>

@endsection