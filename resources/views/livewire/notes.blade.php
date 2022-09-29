<div class="notes">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.notes')) }}</h6>
    <hr />--}}
    @if(! $pinned)
    <form wire:submit.prevent="create" id="inputCreateForm">
        @include('laravel-crm::livewire.components.partials.note.form-fields')
        <div class="form-group">
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
    <hr/>
    @endif
    <ul class="list-unstyled">
        @foreach($notes as $note)
            @livewire('note',[
                'note' => $note
            ], key($note->id))
        @endforeach
    </ul>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", "#inputCreateForm input[name='noted_at']", function () {
                    @this.set('noted_at', $(this).val());
                });
            });
        </script>
    @endpush
</div>


