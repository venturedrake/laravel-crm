@extends('laravel-crm::layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            @include('laravel-crm::layouts.partials.nav-settings')
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="roles" role="tabpanel">
                    <h3 class="mb-3"> {{ ucfirst(__('laravel-crm::lang.labels')) }}  @can('create crm labels')<span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.labels.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_label')) }}</a></span>@endcan</h3>
                    <div class="table-responsive">
                        <table class="table mb-0 card-table table-hover">
                            <thead>
                            <tr>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.color')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.created')) }}</th>
                                <th scope="col">{{ ucfirst(__('laravel-crm::lang.updated')) }}</th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($labels as $label)
                                <tr class="has-link" data-url="{{ url(route('laravel-crm.labels.show',$label)) }}">
                                    <td>{{ $label->name }}</td>
                                    <td>
                                        <span class="badge badge-primary" style="background-color: #{{ $label->hex }}; padding: 6px 8px;">
                                            #{{ $label->hex }}
                                        </span>
                                    </td>
                                    <td>{{ $label->created_at->format($dateFormat) }}</td>
                                    <td>{{ $label->updated_at->format($dateFormat) }}</td>
                                    <td class="disable-link text-right">
                                        @can('view crm labels')
                                        <a href="{{  route('laravel-crm.labels.show',$label) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                        @endcan
                                        @can('edit crm labels')
                                            <a href="{{  route('laravel-crm.labels.edit',$label) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                        @endcan
                                        @can('delete crm labels')
                                        <form action="{{ route('laravel-crm.labels.destroy',$label) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.label') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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
        @if($labels instanceof \Illuminate\Pagination\LengthAwarePaginator )
            @component('laravel-crm::components.card-footer')
                {{ $labels->links() }}
            @endcomponent
        @endif
    </div>

@endsection