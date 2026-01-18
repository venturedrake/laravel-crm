<div class="crm-content">
    {{-- HEADER --}}
    <x-crm-header title="{{ $organization->name }}" class="mb-5" progress-indicator >
            
        {{-- ACTIONS --}}
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back_to_organizations')) }}" link="{{ url(route('laravel-crm.organizations.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive />
            | <livewire:crm-activity-menu /> |
            @can('edit crm organizations')
                <x-mary-button link="{{ url(route('laravel-crm.organizations.edit', $organization)) }}" icon="o-pencil-square" class="btn-sm btn-square btn-outline" responsive />
            @endcan
            @can('delete crm organizations')
                <x-mary-button onclick="modalDeleteOrganization{{ $organization->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="organization" id="{{ $organization->id }}" /> 
            @endcan
        </x-slot:actions>
    </x-crm-header>
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="grid gap-y-5">
            <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
                <div class="grid gap-y-3">
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.type')) }}</strong>
                        <span>
                            {{ $organization->organizationType->name ?? null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.vat_number')) }}</strong>
                        <span>
                            {{ $organization->vat_number }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.industry')) }}</strong>
                        <span>
                            {{ $organization->industry->name ?? null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.timezone')) }}</strong>
                        <span>
                            {{ $organization->timezone->name ?? null }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.number_of_employees')) }}</strong>
                        <span>
                            {{ $organization->number_of_employees }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.annual_revenue')) }}</strong>
                        <span>
                            {{ money($organization->annual_revenue, $organization->currency) }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.linkedin')) }}</strong>
                        <span>
                            @if($organization->linkedin)
                            <a href="https://linkedin.com/company/{{ $organization->linkedin }}" class="link link-hover link-primary" target="_blank">https://linkedin.com/company/{{ $organization->linkedin }}</a>
                            @endif    
                        </span>
                    </div>
                    
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                        <span>
                        {{ $organization->description }}
                        </span>
                    </div>
                    @foreach($organization->phones as $phone)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucfirst($phone->type) }} {{ ucfirst(__('laravel-crm::lang.phone')) }}</strong>
                            <span>
                                <a href="tel:{{ $phone->number }}">{{ $phone->number }}</a> {{ ($phone->primary) ? '(Primary)' : null }}
                            </span>
                        </div>
                    @endforeach
                    @foreach($organization->emails as $email)
                        <div class="flex flex-row gap-5">
                            <strong>{{ ucfirst($email->type) }} {{ ucfirst(__('laravel-crm::lang.email')) }}</strong>
                            <span>
                                <a href="mailto:{{ $email->address }}">{{ $email->address }}</a> {{ ($email->primary) ? '(Primary)' : null }}
                            </span>
                        </div>
                    @endforeach
                    @foreach($organization->addresses as $address)
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
                        @foreach($organization->labels as $label)
                            <x-mary-badge value="{{ $label->name }}" class="badge-sm text-white" style="border-color: #{{ $label->hex }}; background-color: #{{ $label->hex }}" />
                        @endforeach
                    </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.integrations')) }}</strong>
                        <span>
                        @if($organization->xeroContact)<img src="/vendor/laravel-crm/img/xero-icon.png" height="20" />@endif
                        </span>
                    </div>
                    <div class="flex flex-row gap-5">
                        <strong>{{ ucfirst(__('laravel-crm::lang.owner')) }}</strong>
                        <span>
                        @if( $organization->ownerUser)<a href="{{ route('laravel-crm.users.show', $organization->ownerUser) }}" class="link link-hover link-primary">{{ $organization->ownerUser->name ?? null }}</a> @else  {{ ucfirst(__('laravel-crm::lang.unallocated')) }} @endif
                        </span>
                    </div>
                </div>
            </x-mary-card>
            <livewire:crm-related-people :model="$organization" />
            <livewire:crm-related-organizations :model="$organization" />
            <livewire:crm-related-deals :model="$organization" />
        </div>
        <div>
            <livewire:crm-activity-tabs :model="$organization" />
        </div>
    </div>
</div>
