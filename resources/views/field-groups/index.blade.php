@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3"> {{ ucfirst(__('laravel-crm::lang.custom_field_groups')) }}  @can('create crm fields')<span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.field-groups.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_custom_field_group')) }}</a></span>@endcan</h3>
                    <div class="table-responsive">
                        <table class="table mb-0 card-table table-hover">
                            <thead>
                            <tr>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.system')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.updated')) }}</th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($fieldGroups as $fieldGroup)
                                <tr class="has-link" data-url="{{ url(route('laravel-crm.field-groups.show',$fieldGroup)) }}">
                                    <td>{{ $fieldGroup->name }}</td>
                                    <td>{{ ($fieldGroup->system == 1) ?  ucfirst(__('laravel-crm::lang.yes'))  : ucfirst(__('laravel-crm::lang.no')) }}</td>
                                    <td>{{ $fieldGroup->created_at->format($dateFormat) }}</td>
                                    <td>{{ $fieldGroup->updated_at->format($dateFormat) }}</td>
                                    <td class="disable-link text-right">
                                        @can('view crm fields')
                                        <a href="{{  route('laravel-crm.field-groups.show', $fieldGroup) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                        @endcan
                                        @can('edit crm fields')
                                            <a href="{{  route('laravel-crm.field-groups.edit', $fieldGroup) }}" class="btn btn-outline-secondary btn-sm {{ ($fieldGroup->system == 1) ? 'disabled' : null }}"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                        @endcan
                                        @can('delete crm fields')
                                        <form action="{{ route('laravel-crm.field-groups.destroy',$fieldGroup) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.field_group') }}" {{ ($fieldGroup->system == 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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
        @if($fieldGroups instanceof \Illuminate\Pagination\LengthAwarePaginator )
            @component('laravel-crm::components.card-footer')
                {{ $fieldGroups->links() }}
            @endcomponent
        @endif
    </div>

@endsection