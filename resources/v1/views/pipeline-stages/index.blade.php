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
                        <h3 class="card-title float-left m-0"> {{ ucfirst(__('laravel-crm::lang.pipeline_stages')) }} </h3> @can('create crm pipelines')<span class="float-right"><a type="button" class="btn btn-primary btn-sm" href="{{ url(route('laravel-crm.pipeline-stages.create')) }}"><span class="fa fa-plus"></span>  {{ ucfirst(__('laravel-crm::lang.add_pipeline_stage')) }}</a></span>@endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-pane active" id="roles" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table mb-0 card-table table-hover">
                                    <thead>
                                    <tr>
                                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.name')) }}</th>
                                        <th scope="col">{{ ucfirst(__('laravel-crm::lang.pipeline')) }}</th>
                                        <th scope="col"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($pipelineStages as $pipelineStage)
                                        <tr class="has-link" data-url="{{ url(route('laravel-crm.pipeline-stages.show',$pipelineStage)) }}">
                                            <td>{{ $pipelineStage->name }}</td>
                                            <td>{{ $pipelineStage->pipeline->name }}</td>
                                            <td class="disable-link text-right">
                                                @can('view crm pipelines')
                                                    <a href="{{ route('laravel-crm.pipeline-stages.show',$pipelineStage) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-eye" aria-hidden="true"></span></a>
                                                @endcan
                                                @can('edit crm pipelines')
                                                    <a href="{{  route('laravel-crm.pipeline-stages.edit',$pipelineStage) }}" class="btn btn-outline-secondary btn-sm"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                                @endcan
                                                @can('delete crm pipelines')
                                                    <form action="{{ route('laravel-crm.pipeline-stages.destroy',$pipelineStage) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                                        {{ method_field('DELETE') }}
                                                        {{ csrf_field() }}
                                                        <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.pipeline_stage') }}"><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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
            </div>
        </div>
    </div>

@endsection