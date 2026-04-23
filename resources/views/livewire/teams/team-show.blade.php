<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $team->name }}" class="mb-5" progress-indicator>
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_teams')) }}" link="{{ url(route('laravel-crm.teams.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            @can('edit crm teams')
                <x-mary-button link="{{ url(route('laravel-crm.teams.edit', $team)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm teams')
                <x-mary-button onclick="modalDeleteTeam{{ $team->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="team" id="{{ $team->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>

    <div class="grid lg:grid-cols-2 gap-5 items-start">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.name')) }}</strong>
                        <span>{{ $team->name }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created_by')) }}</strong>
                        <span>{{ $team->userCreated->name ?? null }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>{{ $team->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>{{ $team->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.users')) }} ({{ $team->users->count() }})" shadow separator>
                <div class="grid gap-y-3">
                    @foreach($team->users as $user)
                        <div class="flex flex-row gap-3 items-center">
                            <x-mary-icon name="fas.user" class="text-sm" />
                            <span>
                                <a href="{{ route('laravel-crm.users.show', $user) }}" class="link link-hover link-primary">{{ $user->name }}</a>
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        </div>
    </div>
</div>

