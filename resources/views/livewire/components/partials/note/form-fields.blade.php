@if(! isset($note))
    {{--<div wire:model="content" x-data @trix-blur="$dispatch('change', $event.target.value)" class="form-group @error('content') text-danger @enderror">
        <span wire:ignore>
            <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
            <trix-editor class="form-control @error('content') is-invalid @enderror" id="content">{{ $note->message ?? null }}</trix-editor>
            @error('content')
            <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
            @enderror
        </span>
    </div>--}}
    <div class="form-group @error('content') text-danger @enderror">
        <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
        <textarea wire:model="content" class="form-control @error('content') is-invalid @enderror" id="textarea_content" name="content" rows="3">{{ $value ?? null }}</textarea>
        @error('content')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </div>
    @include('laravel-crm::partials.form.text',[
      'name' => 'noted_at',
      'label' => ucfirst(__('laravel-crm::lang.noted_at')),
      'attributes' => [
          'wire:model.debounce.10000ms' => 'noted_at',
          'autocomplete' => 'off',
          'role' => 'presentation'
      ]
    ])
@else
    {{--<div wire:model="content" x-data @trix-blur="$dispatch('change', $event.target.value)" class="form-group @error('note.content') text-danger @enderror">
        <input id="content_{{ $note->id }}" value="{{ $note->content }}" type="hidden">
        <span wire:ignore>
            <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
            <trix-editor input="content_{{ $note->id }}" class="form-control @error('note.content') is-invalid @enderror"></trix-editor>
            @error('note.content')
            <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
            @enderror
        </span>
    </div>--}}
    <div class="form-group @error('content') text-danger @enderror">
        <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
        <textarea wire:model="content" class="form-control @error('content') is-invalid @enderror" id="textarea_content" name="content" rows="3">{{ $value ?? null }}</textarea>
        @error('content')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </div>
    @include('laravel-crm::partials.form.text',[
      'name' => 'noted_at',
      'label' => ucfirst(__('laravel-crm::lang.noted_at')),
      'attributes' => [
          'wire:model.debounce.10000ms' => 'noted_at',
          'autocomplete' => 'off',
          'role' => 'presentation'
      ]
    ])
@endif    

