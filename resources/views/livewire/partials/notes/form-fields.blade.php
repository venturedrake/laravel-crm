<div wire:model="content" x-data @trix-blur="$dispatch('input', $event.target.value)" class="form-group @error('content') text-danger @enderror">
    <span wire:ignore>
        <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
        <trix-editor class="form-control @error('content') is-invalid @enderror" id="content">{{ $value ?? null }}</trix-editor>
        @error('content')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </span>
</div>
@include('laravel-crm::partials.form.text',[
  'name' => 'noted_at',
  'label' => ucfirst(__('laravel-crm::lang.noted_at')),
  'attributes' => [
      'wire:model.debounce.10000ms' => 'noted_at'  
  ]
])