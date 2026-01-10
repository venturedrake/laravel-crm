<div class="crm-content">
    <x-crm-header title="{{ ucfirst(__('laravel-crm::lang.role')) }}: {{ $role->name }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_roles')) }}" link="{{ url(route('laravel-crm.roles.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            @can('edit crm roles')
                @if(in_array($role->name, ['Owner','Admin']))
                    <x-mary-button icon="o-pencil-square" class="btn-sm btn-square btn-outline" disabled />
                @else
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.roles.edit', $role)) }}" class="btn-sm btn-square btn-outline" />
                @endif
            @endcan
            @can('delete crm roles')
                @if(in_array($role->name, ['Owner','Admin']))
                    <x-mary-button icon="o-trash" class="btn-sm btn-square btn-error" disabled />
                @else
                    <x-mary-button onclick="modalDeleteRole{{ $role->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="role" id="{{ $role->id }}"  />
                @endif
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                           {{ $role->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.CRM_Role')) }}</strong>
                        <span>
                            {{ ucfirst(__('laravel-crm::lang.yes')) }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.permission')) }}" shadow separator>
                @foreach($role->permissions as $permission)
                    <span class="badge badge-sm badge-neutral">{{ $permission->name }}</span>
                @endforeach
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.users')) }}" shadow separator>
                @foreach($role->users as $user)
                    <p> <x-mary-icon name="fas.user" class="mr-1" /> {{ $user->name }}</p>
                @endforeach
            </x-mary-card>
        </div>
    </div>
</div>
