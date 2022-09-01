<div class="notes">
   {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.notes')) }}</h6>
   <hr />--}}
   <form wire:submit.prevent="create">
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
            'wire:model.debounce.10000ms' => 'noted_at'  
        ]
      ])
      <div class="form-group">
         <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
      </div>
   </form>
   <hr />
   <ul class="list-unstyled">
   @foreach($notes as $note)
      <li class="media">
         <div class="card w-100 mb-2">
            <div class="card-body">
               {{--<img src="..." class="mr-3" alt="...">--}}
               <div class="media-body">
                  @if($note->relatedNote)
                     <h5 class="mt-0 mb-1">{{ $note->relatedNote->created_at->diffForHumans() }} - {{ $note->relatedNote->createdByUser->name }}</h5>
                     <p class="pb-0 mb-2">
                        @if($note->relatedNote->noteable instanceof \VentureDrake\LaravelCrm\Models\Person)
                           <span class="fa fa-user mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.people.show', $note->relatedNote->noteable) }}">{{ $note->relatedNote->noteable->name }}</a>
                        @elseif($note->relatedNote->noteable instanceof \VentureDrake\LaravelCrm\Models\Organisation)
                           <span class="fa fa-building mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.organisations.show', $note->relatedNote->noteable) }}">{{ $note->relatedNote->noteable->name }}</a>
                        @endif
                     </p>
                     {{ $note->relatedNote->content }}
                  @else   
                     <h5 class="mt-0 mb-1">{{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}</h5>
                     {{ $note->content }}
                      @if($note->noted_at)
                          <br />
                           <span class="badge badge-secondary">{{ ucfirst(__('laravel-crm::lang.noted_at')) }} {{ $note->noted_at->format('h:i A') }} on {{ $note->noted_at->toFormattedDateString() }}</span>
                      @endif    
                  @endif   
               </div>
            </div>
         </div>
      </li>
   @endforeach
   </ul>
@push('livewire-js')
    <script>
        $(document).ready(function () {
            $(document).on("change", "input[name='noted_at']", function() {
                @this.set('noted_at', $(this).val());
            });
        });
    </script>
@endpush    
</div>


