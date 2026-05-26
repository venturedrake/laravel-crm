@extends('laravel-crm::layouts.portal')

@section('content')
    <div class="py-10">
        <x-mary-card shadow class="max-w-md mx-auto">
            <h1 class="text-xl font-bold mb-4">{{ ucfirst(__('laravel-crm::lang.register')) }}</h1>

            @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <ul class="text-sm m-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('laravel-crm.portal.register.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="label" for="portal-register-name">
                        <span class="label-text">{{ ucfirst(__('laravel-crm::lang.name')) }}</span>
                    </label>
                    <input id="portal-register-name" name="name" type="text" required autofocus
                           value="{{ old('name') }}"
                           class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label" for="portal-register-email">
                        <span class="label-text">{{ ucfirst(__('laravel-crm::lang.email_address')) }}</span>
                    </label>
                    <input id="portal-register-email" name="email" type="email" required
                           value="{{ old('email') }}"
                           class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label" for="portal-register-password">
                        <span class="label-text">{{ ucfirst(__('laravel-crm::lang.password')) }}</span>
                    </label>
                    <input id="portal-register-password" name="password" type="password" required minlength="8"
                           class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label" for="portal-register-password-confirmation">
                        <span class="label-text">{{ ucfirst(__('laravel-crm::lang.confirm_password')) }}</span>
                    </label>
                    <input id="portal-register-password-confirmation" name="password_confirmation" type="password" required minlength="8"
                           class="input input-bordered w-full" />
                </div>

                <button type="submit" class="btn btn-primary w-full mt-4">
                    {{ ucfirst(__('laravel-crm::lang.register')) }}
                </button>
            </form>

            <p class="text-sm text-center mt-4">
                <span class="text-base-content/70">{{ ucfirst(__('laravel-crm::lang.already_have_account')) }}</span>
                <a href="{{ route('laravel-crm.portal.login') }}" class="link link-primary">
                    {{ ucfirst(__('laravel-crm::lang.login')) }}
                </a>
            </p>
        </x-mary-card>
    </div>
@endsection
