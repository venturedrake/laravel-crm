@if(! isset($task))
    <div class="form-group @error('name') text-danger @enderror">
        <label>{{ ucfirst(__('laravel-crm::lang.name')) }}</label>
        <input wire:model="name" type="text"  class="form-control @error('name') is-invalid @enderror" id="name" name="name" rows="3" />
        @error('name')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </div>
    {{--<div wire:model="content" x-data @trix-blur="$dispatch('change', $event.target.value)" class="form-group @error('content') text-danger @enderror">
        <span wire:ignore>
            <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
            <trix-editor class="form-control @error('content') is-invalid @enderror" id="content">{{ $note->message ?? null }}</trix-editor>
            @error('content')
            <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
            @enderror
        </span>
    </div>--}}
    <div class="form-group @error('description') text-danger @enderror">
        <label>{{ ucfirst(__('laravel-crm::lang.description')) }}</label>
        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="textarea_description" name="description" rows="3">{{ $value ?? null }}</textarea>
        @error('description')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </div>
    @include('laravel-crm::partials.form.text',[
      'name' => 'due_at',
      'label' => ucfirst(__('laravel-crm::lang.due')),
      'attributes' => [
          'wire:model.debounce.10000ms' => 'due_at',
          'autocomplete' => \Illuminate\Support\Str::random(),
      ]
    ])
@else
    <div class="form-group @error('name') text-danger @enderror">
        <label>{{ ucfirst(__('laravel-crm::lang.name')) }}</label>
        <input wire:model="name" type="text"  class="form-control @error('name') is-invalid @enderror" id="name" name="name" rows="3" />
        @error('name')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </div>
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
    <div class="form-group @error('description') text-danger @enderror">
        <label>{{ ucfirst(__('laravel-crm::lang.description')) }}</label>
        <textarea wire:model="description" class="form-control @error('description') is-invalid @enderror" id="textarea_description" name="description" rows="3">{{ $value ?? null }}</textarea>
        @error('description')
        <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
        @enderror
    </div>
    @include('laravel-crm::partials.form.text',[
      'name' => 'due_at',
      'label' => ucfirst(__('laravel-crm::lang.due')),
      'attributes' => [
          'wire:model.debounce.10000ms' => 'due_at',
          'autocomplete' => \Illuminate\Support\Str::random(),
      ]
    ])
@endif    

