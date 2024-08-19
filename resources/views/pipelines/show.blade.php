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
                        <h3 class="mb-0">{{ ucfirst(__('laravel-crm::lang.pipeline')) }}: {{ $pipeline->name }} <span class="float-right">
                            <a type="button" class="btn btn-outline-secondary btn-sm" href="{{ url(route('laravel-crm.pipelines.index')) }}"><span class="fa fa-angle-double-left"></span> {{ ucfirst(__('laravel-crm::lang.back_to_pipelines')) }}</a> | 
                                @can('edit crm pipelines')
                                    <a href="{{ url(route('laravel-crm.pipelines.edit', $pipeline)) }}" type="button" class="btn btn-outline-secondary btn-sm {{ ($pipeline->system == 1) ? 'disabled' : null }}"><span class="fa fa-edit" aria-hidden="true"></span></a>
                                @endcan
                                {{--@can('delete crm pipelines')
                                    <form action="{{ route('laravel-crm.pipelines.destroy',$pipeline) }}" method="POST" class="form-check-inline mr-0 form-delete-button">
                                        {{ method_field('DELETE') }}
                                        {{ csrf_field() }}
                                        <button class="btn btn-danger btn-sm" type="submit" data-model="{{ __('laravel-crm::lang.pipeline') }}" {{ ($pipeline->system == 1) ? 'disabled' : null }}><span class="fa fa-trash-o" aria-hidden="true"></span></button>
                                    </form>
                                @endcan--}}
                        </span></h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6 border-right">
                                <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.stages')) }}</h6>
                                <hr />
                                @foreach($pipeline->pipelineStages as $stage)
                                    <p>{{ $stage->name }}</p>
                                @endforeach
                            </div>
                            <div class="col-sm-6">
                                <h6 class="text-uppercase section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.attached_to')) }}</span></h6>
                                <hr />
                                {{ ucwords(\Illuminate\Support\Str::snake(class_basename($pipeline->model), ' ')) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endsection