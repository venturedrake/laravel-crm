<ul class="nav nav-tabs card-header-tabs" id="activitiesNav" role="tablist">
    @can('view crm activities')
    <li class="nav-item">
        <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.activities') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.activities.index')) }}" role="tab" aria-controls="activities" aria-selected="true">{{ ucwords(__('laravel-crm::lang.activity')) }}</a>
    </li>
    @endcan
    @can('view crm notes')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.notes') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.notes.index')) }}" role="tab" aria-controls="notes" aria-selected="true">{{ ucwords(__('laravel-crm::lang.notes')) }}</a>
        </li>
    @endcan
    @can('view crm tasks')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.tasks') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.tasks.index')) }}" role="tab" aria-controls="tasks" aria-selected="true">{{ ucwords(__('laravel-crm::lang.tasks')) }}</a>
        </li>
    @endcan
    @can('view crm calls')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.calls') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.calls.index')) }}" role="tab" aria-controls="calls" aria-selected="true">{{ ucwords(__('laravel-crm::lang.calls')) }}</a>
        </li>
    @endcan
    @can('view crm meetings')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.meetings') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.meetings.index')) }}" role="tab" aria-controls="meetings" aria-selected="true">{{ ucwords(__('laravel-crm::lang.meetings')) }}</a>
        </li>
    @endcan
    @can('view crm lunches')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.lunches') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.lunches.index')) }}" role="tab" aria-controls="lunches" aria-selected="true">{{ ucwords(__('laravel-crm::lang.lunches')) }}</a>
        </li>
    @endcan
    @can('view crm files')
        <li class="nav-item">
            <a class="nav-link {{ (strpos(Route::currentRouteName(), 'laravel-crm.files') === 0) ? 'active' : '' }}" href="{{ url(route('laravel-crm.files.index')) }}" role="tab" aria-controls="lunches" aria-selected="true">{{ ucwords(__('laravel-crm::lang.files')) }}</a>
        </li>
    @endcan
</ul>