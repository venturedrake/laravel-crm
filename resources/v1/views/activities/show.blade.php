@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header"><h3 class="card-title float-left m-0">{{ $activity->title }}</h3>
            <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.activities.index')) }}"><span class="fa fa-angle-double-left"></span> Back to activities</a> | 
                <a href="{{ url(route('laravel-crm.activities.edit', $activity)) }}" type="button" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form action="{{ route('laravel-crm.activities.destroy',$activity) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="person"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
            </span>
        </div>
        <div class="card-body card-show">
            <div class="row">
                <div class="col-sm-6 border-right">
                    ...
                </div>
                <div class="col-sm-6">
                    ...
                </div>
            </div>
        </div>
    </div>

@endsection