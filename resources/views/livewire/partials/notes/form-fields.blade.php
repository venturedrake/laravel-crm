@if(! isset($note))
    <div wire:model="content" x-data @trix-blur="$dispatch('change', $event.target.value)" class="form-group @error('content') text-danger @enderror">
        <span wire:ignore>
            <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
            <trix-editor class="form-control @error('content') is-invalid @enderror" id="content">{{ $note->message ?? null }}</trix-editor>
            @error('content')
            <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
            @enderror
        </span>
    </div>
@else
    <div x-data @trix-blur="$dispatch('input', $event.target.value)" class="form-group @error('content') text-danger @enderror">
            <input wire:model="notes.{{ $note->id }}.content"  id="content_{{ $note->id }}" value="{{ $note->content }}" type="hidden">
            <span wire:ignore>
                <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
               
                <trix-editor input="content_{{ $note->id }}" class="form-control @error('content') is-invalid @enderror"></trix-editor>
                @error('content')
                <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
                @enderror
            </span>
    </div>
@endif    

@include('laravel-crm::partials.form.text',[
  'name' => 'noted_at',
  'label' => ucfirst(__('laravel-crm::lang.noted_at')),
  'attributes' => [
      'wire:model.debounce.10000ms' => 'noted_at'  
  ]
])