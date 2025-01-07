<div>
    {{--<h6 class="text-uppercase mt-0">{{ ucfirst(__('laravel-crm::lang.planned')) }}</h6>
    <hr />

    <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.complete')) }}</h6>
    <hr />--}}
    @foreach($activities as $activity)
        @include('laravel-crm::activities.partials.activity', $activity)
    @endforeach
</div>
