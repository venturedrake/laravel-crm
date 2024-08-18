@extends('laravel-crm::layouts.app')

@section('content')

    <div class="container-fluid pl-0">
        <div class="row">
            <div class="col col-md-2">
                <div class="card">
                    <div class="card-body py-3 px-2">
                        @include('laravel-crm::layouts.partials.nav-settings')
                    </div>
                </div>
            </div>
            <div class="col col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">{{ ucfirst(__('laravel-crm::lang.custom_field_group')) }}: {{ $fieldGroup->name }} <span class="float-right">
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
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6 border-right">
                                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.details')) }}</h6>
                                <hr />
                                <dl class="row">
                                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.system')) }}</dt>
                                    <dd class="col-sm-9">{{ ($fieldGroup->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</dd>
                                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.handle')) }}</dt>
                                    <dd class="col-sm-9">{{ $fieldGroup->handle }}</dd>
                                </dl>
                            </div>
                            <div class="col-sm-6">
                                <h6 class="text-uppercase section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.fields')) }}</span></h6>
                                <hr />
                                <div class="table-responsive">
                                    <table class="table mb-0 card-table table-hover">
                                        <thead>
                                        <tr>
                                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.type')) }}</th>
                                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.group')) }}</th>
                                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.required')) }}</th>
                                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.default')) }}</th>
                                            <th scope="col">{{ ucfirst(__('laravel-crm::lang.system')) }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($fieldGroup->fields as $field)
                                            <tr class="has-link" data-url="{{ url(route('laravel-crm.fields.show',$field)) }}">
                                                <td>{{ ucwords(str_replace('_',' ',$field->type)) }}</td>
                                                <td>{{ $field->fieldGroup->name ?? null }}</td>
                                                <td>{{ $field->name }}</td>
                                                <td>{{ ($field->required == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                                                <td>{{ $field->default }}</td>
                                                <td>{{ ($field->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection