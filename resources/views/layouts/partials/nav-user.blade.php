<!-- Teams Dropdown -->
@if (class_exists('\Laravel\Jetstream\Jetstream') && Laravel\Jetstream\Jetstream::hasTeamFeatures())
    <li class="nav-item dropdown">
        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
            {{ Auth::user()->currentTeam->name }} <span class="caret"></span>
        </a>

        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
            <!-- Team Management -->
            <div class="block px-4 py-2 text-xs text-gray-400">
                {{ __('Manage Team') }}
            </div>

            <!-- Team Settings -->
            <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                {{ __('Team Settings') }}
            </x-dropdown-link>

            @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
                <x-dropdown-link href="{{ route('teams.create') }}">
                    {{ __('Create New Team') }}
                </x-dropdown-link>
            @endcan

            <!-- Team Switcher -->
            @if (Auth::user()->allTeams()->count() > 1)
                <div class="border-t border-gray-200"></div>

                <div class="block px-4 py-2 text-xs text-gray-400">
                    {{ __('Switch Teams') }}
                </div>

                @foreach (Auth::user()->allTeams() as $team)
                    <x-switchable-team :team="$team" />
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