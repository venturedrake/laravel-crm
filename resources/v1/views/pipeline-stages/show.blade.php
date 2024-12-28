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
                        <h3 class="mb-0">{{ ucfirst(__('laravel-crm::lang.pipeline_stage')) }}: {{ $pipelineStage->name }} <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.pipeline-stages.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_pipeline_stages')) }}</a> | 
                                @can('edit crm pipelines')
                                    <a href="{{ url(route('laravel-crm.pipeline-stages.edit', $pipelineStage)) }}" type="button" class="btn btn-outline-secondary btn-sm {{ ($pipelineStage->system == 1) ? 'disabled' : null }}"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                @endcan
                                @can('delete crm pipelines')
                                    <form action="{{ route('laravel-crm.pipeline-stages.destroy',$pipelineStage) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                        {{ method_field('DELETE') }}
                                        {{ csrf_field() }}
                                        <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.pipeline') }}" {{ ($pipelineStage->system == 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
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
                                    <dt class="col-sm-3 text-right">{{ ucfirst(__('laravel-crm::lang.description')) }}</dt>
                                    <dd class="col-sm-9">{{ $pipelineStage->description }}</dd>
                                </dl>
                            </div>
                            <div class="col-sm-6">
                                <h6 class="text-uppercase section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.pipeline')) }}</span></h6>
                                <hr />
                                {{ $pipelineStage->pipeline->name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endsection