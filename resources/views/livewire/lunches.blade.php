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
                $(document).on("change", ".lunches #inputCreateForm input[name='start_at']", function () {
                @this.set('start_at', $(this).val());
                });

                $(document).on("change", ".lunches #inputCreateForm input[name='finish_at']", function () {
                @this.set('finish_at', $(this).val());
                });

                $(document).on("change", '.lunches select[name="guests[]"]', function (e) {
                    var data = $('select[name="guests[]"]').select2("val");
                @this.set('guests', data);
                });

                window.addEventListener('lunchEditModeToggled', event => {
                    bindDateTimePicker_Lunch();
                    bindSelect2_Lunch();
                });

                window.addEventListener('lunchAddOn', event => {
                    $('.nav-activities li a#tab-lunches').tab('show')
                    bindDateTimePicker_Lunch()
                    bindSelect2_Lunch();
                });

                $('.nav-tabs a#tab-lunches').on('shown.bs.tab', function(event){
                    bindDateTimePicker_Lunch()
                    bindSelect2_Lunch();
                });

                window.addEventListener('lunchFieldsReset', event => {
                    bindDateTimePicker_Lunch();
                    bindSelect2_Lunch();
                });
            });

            function bindDateTimePicker_Lunch(){
                $('.lunches input[name="start_at"]').datetimepicker({
                    timepicker:true,
                    format: '{{ $dateFormat }} H:i',
                });
                $('.lunches input[name="finish_at"]').datetimepicker({
                    timepicker:true,
                    format: '{{ $dateFormat }} H:i',
                });
            }

            function bindSelect2_Lunch(){
                $('.lunches select[name="guests[]"]').select2();
            }
        </script>
    @endpush
</div>


