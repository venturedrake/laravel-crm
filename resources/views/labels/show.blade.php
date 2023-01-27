@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <h3 class="mb-3"> {{ $label->name }} <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.labels.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_labels')) }}</a> | 
                @can('edit crm labels')
                <a href="{{ url(route('laravel-crm.labels.edit', $label)) }}" type="button" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm labels')    
                <form action="{{ route('laravel-crm.labels.destroy',$label) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.label') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span></h3>

            <div class="row">
                <div class="col-sm-12">
                    <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                    <hr />
                    <dl class="row">
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.color')) }}</dt>
                        <dd class="col-sm-9">
                            <span class="badge badge-primary" style="background-color: #{{ $label->hex }}; padding: 6px 8px;">
                                #{{ $label->hex }}
                            </span>
                        </dd>
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.description')) }}</dt>
                        <dd class="col-sm-9">{{ $label->description }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection