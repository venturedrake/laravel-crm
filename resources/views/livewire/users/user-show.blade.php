<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $user->name }}" class="mb-5" progress-indicator>
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_users')) }}" link="{{ url(route('laravel-crm.users.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> | 
            @can('edit crm users')
                <x-mary-button link="{{ url(route('laravel-crm.users.edit', $user)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm users')
                @if(auth()->user()->id == $user->id)
                    <x-mary-button icon="o-trash" class="btn-sm btn-square btn-error" disabled />
                @else
                    <x-mary-button onclick="modalDeleteTask{{ $user->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                    <x-crm-delete-confirm model="task" id="{{ $user->id }}" />
                @endif    
            @endcan
        </x-slot:actions>
    </x-crm-header>

    <div class="grid lg:grid-cols-2 gap-5 items-start">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.name')) }}</strong>
                        <span>{{ $user->name }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.email')) }}</strong>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.email_verified')) }}</strong>
                        <span>{{ ($user->email_verified_at) ? $user->email_verified_at->format($dateFormat.' '.$timeFormat) : null }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.CRM_Access')) }}</strong>
                        <span>{{ ($user->crm_access) ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.CRM_Role')) }}</strong>
                        <span>{{ $user->roles()->first()->name ?? null }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                        <span>{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.last_online')) }}</strong>
                        <span>{{ ($user->last_online_at) ?  \Carbon\Carbon::parse($user->last_online_at)->diffForHumans() :  'Never'}}</span>
                    </div>
                </div>
            </x-mary-card>
            
        </div>
        @hasteamsenabled
            @can('view crm teams')
                <div class="grid gap-y-5">
                    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.teams')) }}  ({{ $user->crmTeams->count() }})" shadow separator>
                        <div class="grid gap-y-3">
                            @foreach($user->crmTeams as $team)
                                <div class="flex flex-row gap-5">
                                    <x-mary-icon name="fas.users" />
                                    <span>
                                        <a href="{{ route('laravel-crm.teams.show', $team) }}" class="link link-hover link-primary">{{$team->name }}</a>
                                    </span>
                                </div>
                                @endforeach
                        </div>
                    </x-mary-card>
                </div>
            @endcan
        @endhasteamsenabled
    </div>
</div>

