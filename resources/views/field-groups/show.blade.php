@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <h3 class="mb-3"> {{ $fieldGroup->name }} <span class="float-right">
                <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.field-groups.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_field_groups')) }}</a> | 
                @can('edit crm fields')
                <a href="{{ url(route('laravel-crm.field-groups.edit', $fieldGroup)) }}" type="button" class="btn btn-outline-secondary btn-sm {{ ($fieldGroup->system == 1) ? 'disabled' : null }}"><span class="fa fa-edit" aria-hidden="true"></span></a>
                @endcan
                @can('delete crm fields')    
                <form action="{{ route('laravel-crm.field-groups.destroy',$fieldGroup) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.field_group') }}" {{ ($fieldGroup->system == 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                </form>
                @endcan
            </span></h3>

          <div class="row">
                <div class="col-sm-12">
                    <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                    <hr />
                    <dl class="row">
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.system')) }}</dt>
                        <dd class="col-sm-9">{{ ($fieldGroup->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</dd>
                        <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.handle')) }}</dt>
                        <dd class="col-sm-9">{{ $fieldGroup->handle }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection