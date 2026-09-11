<div class="crm-content">
    {{-- HEADER --}}
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.users')) }}" progress-indicator>
        {{--  SEARCH --}}
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.search_users')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>

        {{-- ACTIONS  --}}
        <x-slot:actions>
            <x-mary-button label="Filters"
                           icon="o-funnel"
                           :badge="$filterCount ?? 0"
                           badge-classes="font-mono badge-primary badge-soft"
                           @click="$wire.showFilters = true"
                           responsive />

           {{-- <x-crm-index-toggle :layout="$layout" model="users"/>--}}

            @can('create crm users')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.import_users')) }}" link="{{ url(route('laravel-crm.users.import')) }}" icon="o-arrow-up-tray" class="btn-outline" responsive />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.invite_user')) }}" link="{{ url(route('laravel-crm.users.invite')) }}" icon="o-paper-airplane" class="btn-outline" responsive />
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create_user')) }}" link="{{ url(route('laravel-crm.users.create')) }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- TABS (DaisyUI radio tabs-lift + tab content) --}}
    <div role="tablist" class="tabs tabs-lift">
        <input type="radio"
               name="user-tabs"
               role="tab"
               class="tab"
               aria-label="{{ ucfirst(__('laravel-crm::lang.registered_users')) }}"
               value="users"
               wire:model.live="tab" />
        <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6">
            <x-mary-table :headers="$headers" :rows="$users" :link="route('laravel-crm.users.show', ['user' => '[id]'])" with-pagination :sort-by="$sortBy" class="whitespace-nowrap">
                @scope('cell_role', $user)
                    {{ $user->roles->first()?->name }}
                @endscope
                @scope('actions', $user)
                @can('view crm users')
                    <x-mary-button icon="o-eye" link="{{ url(route('laravel-crm.users.show', $user)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('edit crm users')
                    <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.users.edit', $user)) }}" class="btn-sm btn-square btn-outline" />
                @endcan
                @can('delete crm users')
                    @if(auth()->user()->id == $user->id)
                        <x-mary-button icon="o-trash" class="btn-sm btn-square btn-error" disabled />
                    @else
                        <x-mary-button onclick="modalDeleteUser{{ $user->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        <x-crm-delete-confirm model="user" id="{{ $user->id }}" />
                    @endif
                @endcan
                @endscope
            </x-mary-table>
        </div>

        @can('create crm users')
            <input type="radio"
                   name="user-tabs"
                   role="tab"
                   class="tab"
                   aria-label="{{ ucfirst(__('laravel-crm::lang.pending_invitations')) }}"
                   value="invitations"
                   wire:model.live="tab" />
            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 p-6">
                <x-mary-table :headers="$invitationHeaders" :rows="$this->invitations" :with-pagination="true" class="whitespace-nowrap">
                    @scope('cell_role', $row)
                        {{ $row->role?->name ?? '—' }}
                    @endscope
                    @scope('cell_invited_by', $row)
                        {{ $row->invitedByUser?->name ?? '—' }}
                    @endscope
                    @scope('cell_sent_at', $row)
                        {{ $row->created_at->diffForHumans() }}
                    @endscope
                    @scope('cell_last_sent', $row)
                        {{ $row->last_sent_at?->diffForHumans() ?? $row->created_at->diffForHumans() }}
                    @endscope
                    @scope('cell_expires', $row)
                        {{ $row->expires_at?->diffForHumans() ?? '—' }}
                    @endscope
                    @scope('actions', $row)
                        <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.resend_invitation')) }}" icon="o-paper-airplane" wire:click="resendInvitation({{ $row->id }})" class="btn-sm btn-outline" spinner />
                        <x-mary-button icon="o-trash" wire:click="deleteInvitation({{ $row->id }})" wire:confirm class="btn-sm btn-square btn-error text-white" spinner />
                    @endscope

                    <x-slot:empty>
                        <div class="p-4 text-center">
                            {{ ucfirst(__('laravel-crm::lang.no_pending_invitations')) }}
                        </div>
                    </x-slot:empty>
                </x-mary-table>
            </div>
        @endcan
    </div>

    {{-- FILTERS --}}
    <x-mary-drawer wire:model="showFilters" title="Filters" class="lg:w-1/3" right separator with-close-button>
        <div class="grid gap-5" @keydown.enter="$wire.showFilters = false">
            <x-mary-choices label="{{ ucfirst(__('laravel-crm::lang.role')) }}" wire:model.live="role_id" :options="$roles" icon="o-shield-check" inline allow-all />
            <x-mary-select label="{{ ucfirst(__('laravel-crm::lang.CRM_Access')) }}" wire:model.live="crm_access" :options="[['id' => '1', 'name' => ucfirst(__('laravel-crm::lang.yes'))], ['id' => '0', 'name' => ucfirst(__('laravel-crm::lang.no'))]]" icon="o-key" placeholder="{{ ucfirst(__('laravel-crm::lang.all')) }}" />
        </div>

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-mary-button label="Done" icon="o-check" class="btn-primary text-white" @click="$wire.showFilters = false" />
        </x-slot:actions>
    </x-mary-drawer>
</div>
