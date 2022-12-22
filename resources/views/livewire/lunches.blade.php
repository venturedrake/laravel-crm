<div class="lunches">
    {{--<h6 class="text-uppercase">{{ ucfirst(__('laravel-crm::lang.lunches')) }}</h6>
    <hr />--}}
    @if($showForm)
        <form wire:submit.prevent="create" id="inputCreateForm">
            @include('laravel-crm::livewire.components.partials.lunch.form-fields')
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{ ucfirst(__('laravel-crm::lang.save')) }}</button>
            </div>
        </form>
        <hr/>
    @endif
    <ul class="list-unstyled">
        @foreach($lunches as $lunch)
            @livewire('lunch',[
                'lunch' => $lunch
            ], key($lunch->id))
        @endforeach
    </ul>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", "#inputCreateForm input[name='start_at']", function () {
                    @this.set('start_at', $(this).val());
                });

                $(document).on("change", "#inputCreateForm input[name='finish_at']", function () {
                    @this.set('finish_at', $(this).val());
                });

                window.addEventListener('lunchEditModeToggled', event => {
                    $('input[name="start_at"]').datetimepicker({
                     timepicker:true,
                     format: 'Y/m/d H:i',
                    });
                    $('input[name="finish_at"]').datetimepicker({
                        timepicker:true,
                        format: 'Y/m/d H:i',
                    });
                });

                window.addEventListener('lunchAddOn', event => {
                    $('.nav-activities li a#tab-lunches').tab('show')
                    $('input[name="start_at"]').datetimepicker({
                        timepicker:true,
                        format: 'Y/m/d H:i',
                    });
                    $('input[name="finish_at"]').datetimepicker({
                        timepicker:true,
                        format: 'Y/m/d H:i',
                    });
                });
            });
        </script>
    @endpush
</div>


