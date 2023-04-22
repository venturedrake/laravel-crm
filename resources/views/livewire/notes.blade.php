<div class="notes">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.notes')) }}</h6>
    <hr />--}}
    @if($showForm)
        @if(! $pinned)
        <form wire:submit.prevent="create" id="inputCreateForm">
            @include('laravel-crm::livewire.components.partials.note.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
            </div>
        </form>
        <hr/>
        @endif
    @endif
    <ul class="list-unstyled">
        @foreach($notes as $note)
            @livewire('note',[
                'note' => $note
            ], key($note->id))
        @endforeach
    </ul>
    @if(! $pinned)
        @push('livewire-js')
            <script>
                $(document).ready(function () {
                    $(document).on("change", "#inputCreateForm input[name='noted_at']", function () {
                        @this.set('noted_at', $(this).val());
                    });

                    window.addEventListener('noteAddOn', event => {
                        $('.nav-activities li a#tab-notes').tab('show')
                        $('input[name="noted_at"]').datetimepicker({
                            timepicker:true,
                            format: '{{ $dateFormat }} H:i',
                        });
                    });

                    window.addEventListener('noteEditModeToggled', event => {
                        bsCustomFileInput.init()
                        $('input[name="noted_at"]').datetimepicker({
                            timepicker:true,
                            format: '{{ $dateFormat }} H:i',
                        });
                    });
                });
            </script>
        @endpush
    @endif
</div>


