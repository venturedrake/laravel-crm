<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $person->name }}" class="mb-5" progress-indicator >
            
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_people')) }}" link="{{ url(route('laravel-crm.people.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
            | <livewire:crm-activity-menu /> |
            @can('edit crm people')
                <x-mary-button link="{{ url(route('laravel-crm.people.edit', $person)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm people')
                <x-mary-button onclick="modalDeletePerson{{ $person->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="person" id="{{ $person->id }}" />            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.first_name')) }}</strong>
                        <span>
                        {{ $person->first_name }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.middle_name')) }}</strong>
                        <span>
                        {{ $person->middle_name }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.last_name')) }}</strong>
                        <span>
                        {{ $person->last_name }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.gender')) }}</strong>
                        <span>
                        {{ ucfirst($person->gender) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.birthday')) }}</strong>
                        <span>
                        {{ ucfirst($person->birthday) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $person->description }}
                        </span>
                    </div>
                    @foreach($person->phones as $phone)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucfirst($phone->type) }} {{ ucfirst(__('laravel-crm::lang.phone')) }}</strong>
                            <span>
                                <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> {{ ($phone->primary) ? '(Primary)' : null }}
                            </span>
                        </div>
                    @endforeach
                    @foreach($person->emails as $email)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucfirst($email->type) }} {{ ucfirst(__('laravel-crm::lang.email')) }}</strong>
                            <span>
                                <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> {{ ($email->primary) ? '(Primary)' : null }}
                            </span>
                        </div>
                    @endforeach
                    @foreach($person->addresses as $address)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ($address->addressType) ? ucfirst($address->addressType->name).' ' : null }}{{ ucfirst(__('laravel-crm::lang.address')) }}</strong>
                            <span>
                                {{ \VentureDrake\LaravelCrm\Http\Helpers\AddressLine\addressSingleLine($address) }} {{ ($address->primary) ? '(Primary)' : null }}
                            </span>
                        </div>
                    @endforeach
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.labels')) }}</strong>
                        <span>
                        @foreach($person->labels as $label)
                            <x-mary-badge value="{{ $label->name }}" class="badge-sm text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                        @endforeach
                    </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $person->ownerUser)<a href="{{ route('laravel-crm.users.show', $person->ownerUser) }}" class="link link-hover link-primary">{{ $person->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <livewire:crm-related-people :model="$person" />
            <livewire:crm-related-organizations :model="$person" />
            <livewire:crm-related-deals :model="$person" />
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$person" />
        </div>
    </div>
</div>
