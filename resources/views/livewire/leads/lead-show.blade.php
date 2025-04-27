<div>
    {{-- HEADER --}}
    <x-mary-header title="{{ $lead->title }}" class="mb-5" progress-indicator >

        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_leads')) }}" link="{{ url(route('laravel-crm.leads.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
            @hasdealsenabled
            @can('edit crm leads')
                | <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.convert')) }}" link="{{ route('laravel-crm.leads.convert-to-deal',$lead) }}" class="btn-sm btn-success text-white" responsive />
            @endcan
            @endhasdealsenabled
            | <livewire:crm-activity-menu /> |
            @can('edit crm leads')
                <x-mary-button link="{{ url(route('laravel-crm.leads.edit', $lead)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm leads')
                <x-mary-button wire:click="delete({{ $lead->id }})" icon="o-trash" class="btn-sm btn-square btn-error text-white" wire:confirm="Are you sure?" spinner />
            @endcan
        </x-slot:actions>
    </x-mary-header>
    <div class="grid lg:grid-cols-2 gap-8">
        <div class="grid gap-y-8">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.tag" />
                        <span>
                        @foreach($lead->labels as $label)
                            <x-mary-badge value="{{ $label->name }}" class="badge-sm text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                        @endforeach
                    </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.dollar-sign" />
                        <span>
                       {{ money($lead->amount, $lead->currency) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.info" />
                        <span>
                        {{ $lead->description }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user" />
                        <span>
                        @if( $lead->ownerUser)<a href="{{ route('laravel-crm.users.show', $lead->ownerUser) }}" class="link link-hover link-primary">{{ $lead->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.contact')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.user-circle" />
                        <span>
                        @if($lead->person)<a href="{{ route('laravel-crm.people.show',$lead->person) }}" class="link link-hover link-primary">{{ $lead->person->name }}</a>@endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.envelope" />
                        <span>
                        @if($email)
                        <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> ({{ ucfirst($email->type) }})
                        @endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.phone" />
                        <span>
                        @if($phone)
                        <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> ({{ ucfirst($phone->type) }})
                        @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.organization')) }}" shadow separator>
                <div class="grid gap-y-5">
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.building" />
                        <span>
                        @if($lead->organization)<a href="{{ route('laravel-crm.organizations.show',$lead->organization) }}">{{ $lead->organization->name }}</a>@endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <x-mary-icon name="fas.map-marker" />
                        <span>
                        {{ ($address) ? \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) : null }}
                        </span>
                    </div>
                </div>
            </x-mary-card>
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$lead" />
        </div>
    </div>
</div>
