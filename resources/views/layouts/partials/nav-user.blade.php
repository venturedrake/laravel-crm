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