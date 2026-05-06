<div class="crm-content">
    <x-mary-header title="{{ ucfirst(__('laravel-crm::lang.sms_templates')) }}" progress-indicator>
        <x-slot:middle class="justify-end!">
            <x-mary-input placeholder="{{ ucfirst(__('laravel-crm::lang.sms_templates')) }}..." wire:model.live.debounce="search" icon="o-magnifying-glass" clearable />
        </x-slot:middle>
        <x-slot:actions>
            @can('create crm sms-templates')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.create')) }}" link="{{ route('laravel-crm.sms-templates.create') }}" icon="o-plus" class="btn-primary text-white" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow>
        <x-mary-table :headers="$headers" :rows="$templates" with-pagination :sort-by="$sortBy">
            @scope('cell_is_system', $template)
                @if($template->is_system)
                    <x-mary-badge value="System" class="badge-info text-white" />
                @else
                    <x-mary-badge value="Custom" class="badge-ghost" />
                @endif
            @endscope
            @scope('cell_created_at', $template)
                {{ $template->created_at?->diffForHumans() }}
            @endscope
            @scope('actions', $template)
                <div class="flex gap-1 justify-end">
                    @can('view crm sms-templates')
                        <x-mary-button icon="o-eye" link="{{ route('laravel-crm.sms-templates.show', $template) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('edit crm sms-templates')
                        @if(! $template->is_system)
                            <x-mary-button icon="o-pencil-square" link="{{ route('laravel-crm.sms-templates.edit', $template) }}" class="btn-sm btn-square btn-outline" />
                        @endif
                    @endcan
                    @can('create crm sms-templates')
                        <x-mary-button icon="o-document-duplicate" link="{{ route('laravel-crm.sms-templates.create', ['clone_from' => $template->id]) }}" class="btn-sm btn-square btn-outline" />
                    @endcan
                    @can('delete crm sms-templates')
                        @if(! $template->is_system)
                            <x-mary-button wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" icon="o-trash" class="btn-sm btn-square btn-error text-white" spinner />
                        @endif
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
