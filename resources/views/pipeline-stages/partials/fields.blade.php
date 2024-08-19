<div class="row">
    <div class="col">
        @include('laravel-crm::partials.form.text',[
         'name' => 'name',
         'label' => ucfirst(trans('laravel-crm::lang.name')),
         'value' => old('name', $pipelineStage->name ?? null),
         'required' => 'true'
       ])

        @include('laravel-crm::partials.form.textarea',[
        'name' => 'description',
        'label' => ucfirst(trans('laravel-crm::lang.description')),
        'value' => old('name', $pipelineStage->description ?? null),
      ])

        @include('laravel-crm::partials.form.select',[
        'name' => 'pipeline_id',
        'label' => ucfirst(trans('laravel-crm::lang.pipeline')),
        'options' => [''=>''] + \VentureDrake\LaravelCrm\Models\Pipeline::pluck('name','id')->toArray(),
        'value' => old('pipeline_id', $pipelineStage->pipeline->id ?? null),
        'required' => 'true'
       ])
    </div>
</div>