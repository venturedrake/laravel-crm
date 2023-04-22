@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3"> {{ ucfirst(__('laravel-crm::lang.custom_fields')) }}  @can('create crm fields')<span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.fields.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_custom_field')) }}</a></span>@endcan</h3>
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
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.updated')) }}</th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($fields as $field)
                                <tr class="has-link" data-url="{{ url(route('laravel-crm.fields.show',$field)) }}">
                                    <td>{{ ucwords(str_replace('_',' ',$field->type)) }}</td>
                                    <td>{{ $field->fieldGroup->name ?? null }}</td>
                                    <td>{{ $field->name }}</td>
                                    <td>{{ ($field->required == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                                    <td>{{ $field->default }}</td>
                                    <td>{{ ($field->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                                    <td>{{ $field->created_at->format($dateFormat) }}</td>
                                    <td>{{ $field->updated_at->format($dateFormat) }}</td>
                                    <td class="disable-link text-right">
                                        @can('view crm fields')
                                        <a href="{{  route('laravel-crm.fields.show', $field) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                        @endcan
                                        @can('edit crm fields')
                                            <a href="{{ route('laravel-crm.fields.edit', $field) }}" class="btn btn-outline-secondary btn-sm {{ ($field->system == 1) ? 'disabled' : null }}"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                        @endcan
                                        @can('delete crm fields')
                                        <form action="{{ route('laravel-crm.fields.destroy',$field) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.field') }}" {{ ($field->system == 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @if($fields instanceof \Illuminate\Pagination\LengthAwarePaginator )
            @component('laravel-crm::components.card-footer')
                {{ $fields->links() }}
            @endcomponent
        @endif
    </div>

@endsection