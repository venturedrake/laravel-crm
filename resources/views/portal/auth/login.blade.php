@extends('laravel-crm::layouts.portal')

@section('content')
    <div class="py-10">
        <x-mary-card shadow class="max-w-md mx-auto">
            <h1 class="text-xl font-bold mb-4">{{ ucfirst(__('laravel-crm::lang.login')) }}</h1>

            @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <ul class="text-sm m-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('laravel-crm.portal.login.attempt') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="label" for="portal-login-email">
                        <span class="label-text">{{ ucfirst(__('laravel-crm::lang.email_address')) }}</span>
                    </label>
                    <input id="portal-login-email" name="email" type="email" required autofocus
                           value="{{ old('email') }}"
                           class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label" for="portal-login-password">
                        <span class="label-text">{{ ucfirst(__('laravel-crm::lang.password')) }}</span>
                    </label>
                    <input id="portal-login-password" name="password" type="password" required
                           class="input input-bordered w-full" />
                </div>

                <label class="flex items-center gap-2 cursor-pointer mt-2">
                    <input type="checkbox" name="remember" value="1" class="checkbox checkbox-sm checkbox-primary" />
                    <span class="text-sm">{{ ucfirst(__('laravel-crm::lang.remember_me')) }}</span>
                </label>

                <button type="submit" class="btn btn-primary w-full mt-4">
                    {{ ucfirst(__('laravel-crm::lang.login')) }}
                </button>
            </form>

            @if(config('laravel-crm.portal.allow_registration', false))
                <p class="text-sm text-center mt-4">
                    <span class="text-base-content/70">{{ ucfirst(__('laravel-crm::lang.dont_have_account')) }}</span>
                    <a href="{{ route('laravel-crm.portal.register') }}" class="link link-primary">
                        {{ ucfirst(__('laravel-crm::lang.register')) }}
                    </a>
                </p>
            @endif
        </x-mary-card>
    </div>
@endsection
