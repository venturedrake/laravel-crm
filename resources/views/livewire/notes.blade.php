<div class="notes">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.notes')) }}</h6>
    <hr />--}}
    <form wire:submit.prevent="create">
        @include('laravel-crm::livewire.components.partials.note.form-fields')
        <div class="form-group">
            <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
        </div>
    </form>
    <hr/>
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
                $(document).on("change", "input[name='noted_at']", function () {
                @this.set('noted_at', $(this).val());
                });
            });
        </script>
    @endpush
</div>


