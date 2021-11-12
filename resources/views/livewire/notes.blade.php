<div class="notes">
   <h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.notes')) }}</h6>
   <hr />
   <div class="form-group">
      <label>{{ ucfirst(__('laravel-crm::lang.add_note')) }}</label>
      <textarea class="form-control" id="textarea_content" name="content" rows="3">{{ $value ?? null }}</textarea>
      @error('content')
      <div class="text-danger invalid-feedback-custom">{{ $message }}</div>
      @enderror
   </div>
   <div class="form-group">
      <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
   </div>
   <br />
   @foreach($notes as $note)
      <div class="media">
         <div class="media-body">
            <h5 class="mt-0">{{ $note->created_at->diffForHumans() }} - {{ $note->createdByUser->name }}</h5>
            {{ $note->content }}
         </div>
      </div>
      @if(!$loop->last)
         <hr />
      @endif   
   @endforeach
</div>


