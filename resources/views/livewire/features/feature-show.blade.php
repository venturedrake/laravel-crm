<div class="crm-content">
    <x-crm-header :title="$feature->title" progress-indicator>
        <x-slot:badges>
            @if($feature->status)
                <x-mary-badge :value="$feature->status->name" class="text-white" :style="'background-color: '.($feature->status->color ?? '#6c757d')" />
            @endif
            @if(! $feature->is_public)
                <x-mary-badge value="Private" class="badge-neutral text-white" />
            @endif
        </x-slot:badges>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }} {{ __('laravel-crm::lang.to') }} {{ __('laravel-crm::lang.features') }}" link="{{ url(route('laravel-crm.features.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
            @if($feature->is_public)
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.public')).' '.__('laravel-crm::lang.view') }}"
                               link="{{ url(route('laravel-crm.portal.features.show', $feature)) }}"
                               external
                               icon="o-arrow-top-right-on-square"
                               class="btn-sm btn-outline"
                               responsive />
            @endif
            @can('edit crm features')
                <x-mary-button icon="o-pencil-square" link="{{ url(route('laravel-crm.features.edit', $feature)) }}" class="btn-sm btn-square btn-outline" />
            @endcan
            @can('delete crm features')
                <x-mary-button onclick="modalDeleteFeature{{ $feature->id }}.showModal()" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                <x-crm-delete-confirm model="feature" id="{{ $feature->id }}" />
            @endcan
        </x-slot:actions>
    </x-crm-header>

    <div class="grid lg:grid-cols-2 gap-5 items-start">
        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.details')) }}" shadow separator>
            <div class="grid gap-y-3">
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.number')) }}</strong>
                    <span>{{ $feature->feature_id }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.created')) }}</strong>
                    <span>{{ $feature->created_at?->diffForHumans() }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.description')) }}</strong>
                    <span>{{ $feature->description }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.created_by')) }}</strong>
                    <span>{{ $feature->submittedBy?->name ?? '-' }}</span>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.activity')) }}" shadow separator>
            <div class="grid gap-y-3">
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.votes')) }}</strong>
                    <span>{{ $feature->votes_count }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.comments')) }}</strong>
                    <span>{{ $feature->comments_count }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>{{ ucfirst(__('laravel-crm::lang.views')) }}</strong>
                    <span>{{ $feature->views_count }}</span>
                </div>
            </div>
        </x-mary-card>
    </div>

    <div class="grid lg:grid-cols-2 gap-5 items-start mt-5">
        <x-mary-card shadow title="{{ ucfirst(__('laravel-crm::lang.votes_over_time')) }}">
            <x-slot:menu>
                <select wire:model.live="chartPeriod" class="select select-primary select-sm font-normal">
                    @foreach($this->chartPeriodOptions() as $option)
                        <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </x-slot:menu>
            <div class="h-72">
                <x-mary-chart wire:model="votesChart" class="!h-full" />
            </div>
        </x-mary-card>

        <x-mary-card shadow title="{{ ucfirst(__('laravel-crm::lang.views_over_time')) }}">
            <div class="h-72">
                <x-mary-chart wire:model="viewsChart" class="!h-full" />
            </div>
        </x-mary-card>
    </div>

    <x-mary-card shadow class="mt-5" title="{{ ucfirst(__('laravel-crm::lang.voters')) }}">
        <livewire:crm-feature-voters :feature="$feature" />
    </x-mary-card>
</div>
