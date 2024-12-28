<li class="media">
    <div class="card w-100 mb-2">
        <div class="card-body">
            {{--<img src="..." class="mr-3" alt="...">--}}
            <div class="media-body">
                <h5 class="mt-0 mb-1">{{ $lunch->name }} @include('laravel-crm::livewire.components.partials.lunch.actions', ['lunch' => $lunch])</h5>
                @if($showRelated)
                    <p class="pb-0 mb-2">
                        @if($lunch->lunchable instanceof \VentureDrake\LaravelCrm\Models\Person)
                            <span class="fa fa-user-circle" aria-hidden="true"></span> <a
                                    href="{{ route('laravel-crm.people.show', $lunch->lunchable) }}">{{ $lunch->lunchable->name }}</a>
                        @elseif($lunch->lunchable instanceof \VentureDrake\LaravelCrm\Models\Organisation)
                            <span class="fa fa-building" aria-hidden="true"></span> <a
                                    href="{{ route('laravel-crm.organisations.show', $lunch->lunchable) }}">{{ $lunch->lunchable->name }}</a>
                        @endif
                    </p>
                @endif
                @include('laravel-crm::livewire.components.partials.lunch.content', ['lunch' => $lunch])
            </div>
        </div>
    </div>
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                $(document).on("change", ".lunches input[name='start_at']", function () {
                    @this.set('start_at', $(this).val());
                });

                $(document).on("change", ".lunches input[name='finish_at']", function () {
                    @this.set('finish_at', $(this).val());
                });

                $(document).on("change", '.lunches select[name="guests[]"]', function (e) {
                    var data = $('.lunches select[name="guests[]"]').select2("val");
                    @this.set('guests', data);
                });
            });
        </script>
    @endpush
</li>

