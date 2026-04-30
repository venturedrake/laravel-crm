<div>
    <x-mary-form wire:submit="updateProfileInformation">
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.profile_information')) }}"
                     subtitle="{{ __('laravel-crm::lang.profile_information_subtitle') }}" separator>
            <div class="grid gap-4">
                @if (\Schema::hasColumn($user->getTable(), 'profile_photo_path'))
                    <div class="flex items-center gap-4">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="rounded-full h-20 w-20 object-cover" />
                        @else
                            <img src="{{ $user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
                                 alt="{{ $user->name }}" class="rounded-full h-20 w-20 object-cover" />
                        @endif

                        <div class="flex flex-col gap-2">
                            <x-mary-file wire:model="photo" accept="image/png, image/jpeg" />
                            @if ($user->profile_photo_path)
                                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.remove_photo')) }}"
                                               class="btn-ghost btn-sm" wire:click="deletePhoto" />
                            @endif
                        </div>
                    </div>
                @endif

                <x-mary-input wire:model="name" label="{{ ucfirst(__('laravel-crm::lang.name')) }}" required />
                <x-mary-input wire:model="email" label="{{ ucfirst(__('laravel-crm::lang.email')) }}" type="email" required />

                @if (in_array(\Illuminate\Contracts\Auth\MustVerifyEmail::class, class_implements($user)) && is_null($user->email_verified_at))
                    <div class="alert alert-warning text-sm">
                        {{ __('laravel-crm::lang.email_unverified') }}
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.save')) }}" type="submit"
                               class="btn-primary" spinner="updateProfileInformation" />
            </x-slot:actions>
        </x-mary-card>
    </x-mary-form>
</div>

