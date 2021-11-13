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
                  <h5 class="mt-0 mb-1">{{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}</h5>
                  {{ $note->content }}
               </div>
            </div>
         </div>
      </li>
   @endforeach
   </ul>
</div>


