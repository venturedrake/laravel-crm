<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.roles')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_role')) }}" link="{{ url(route('laravel-crm.roles.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
        </x-slot:actions>
    </x-mary-header>
    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$roles" link="/roles/{id}" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
           {{-- @scope('cell_color', $label)
                <span class="badge text-white" style="background-color: #{{ $label->hex }}; padding: 6px 8px;">
                    #{{ $label->hex }}
                </span>
            @endscope--}}
            @scope('actions', $role)
                @can('view crm roles')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.roles.show', $role)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
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
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
