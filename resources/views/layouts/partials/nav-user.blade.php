<!-- Teams Dropdown -->
@if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::hasTeamFeatures())
    <li class="nav-item dropdown">
        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
            {{ Auth::user()->currentTeam->name }} <span class="caret"></span>
        </a>

        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
            <!-- Team Management -->
            <h6 class="dropdown-header">{{ __('Manage Team') }}r</h6>
            
            <!-- Team Settings -->
            <a class="dropdown-item" href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">{{ __('Team Settings') }}</a>

            @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                <a class="dropdown-item" href="{{ route('teams.create') }}">{{ __('Create New Team') }}</a>
            @endcan

            <!-- Team Switcher -->
            @if (Auth::user()->allTeams()->count() > 1)
                <div class="dropdown-divider"></div>
            
                <h6 class="dropdown-header">{{ __('Switch Teams') }}r</h6>

                @foreach (Auth::user()->allTeams() as $team)
                    <form method="POST" action="{{ route('current-team.update') }}" x-data>
                        @method('PUT')
                        @csrf

                        <!-- Hidden Team ID -->
                        <input type="hidden" name="team_id" value="{{ $team->id }}">

                        <x-dynamic-component :component="$component" href="#" x-on:click.prevent="$root.submit();">
                            <div class="flex items-center">
                                @if (Auth::user()->isCurrentTeam($team))
                                    <svg class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif

                                <div class="truncate">{{ $team->name }}</div>
                            </div>
                        </x-dynamic-component>
                    </form>
                @endforeach
            @endif
        </div>
    </li>
@endif    

<li class="nav-item dropdown">
    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
        {{ Auth::user()->name }} <span class="caret"></span>
    </a>

    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
        @if(Route::has('profile.edit'))
            <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('Profile') }}</a>
        @elseif(Route::has('profile.show'))
            <a class="dropdown-item" href="{{ route('profile.show') }}">{{ __('Profile') }}</a> 
        @endif
        @if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::hasApiFeatures())
            <a class="dropdown-item" href="{{ route('api-tokens.index') }}">{{ __('API Tokens') }}</a>
        @endif        
        <a class="dropdown-item" href="{{ route('laravel-crm.logout') }}"
           onclick="event.preventDefault(); 
            document.getElementById('logout-form').submit();">
            {{ __('Logout') }}
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</li>    