<div class="crm-content">
    <x-crm-header title="{{ $feature->title }}" progress-indicator>
        <x-slot:badges>
            @if($feature->status)
                <x-mary-badge :value="$feature->status->name" class="text-white" :style="'background-color: '.($feature->status->color ?? '#6c757d')" />
            @endif
            @if(! $feature->is_public)
                <x-mary-badge value="Private" class="badge-neutral text-white" />
            @endif
        </x-slot:badges>
        <x-slot:actions>
            <x-mary-button label="Back to Features" link="{{ url(route('laravel-crm.features.index')) }}" icon="fas.angle-double-left" class="btn-sm btn-outline" responsive /> |
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
        <x-mary-card title="Details" shadow separator>
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
                    <strong>Submitted by</strong>
                    <span>{{ $feature->submittedBy?->name ?? '-' }}</span>
                </div>
            </div>
        </x-mary-card>

        <x-mary-card title="Engagement" shadow separator>
            <div class="grid gap-y-3">
                <div class="flex flex-row gap-5">
                    <strong>Votes</strong>
                    <span>{{ $feature->votes_count }}</span>
                </div>
                <div class="flex flex-row gap-5">
                    <strong>Comments</strong>
                    <span>{{ $feature->comments_count }}</span>
                </div>
            </div>
        </x-mary-card>
    </div>
</div>
