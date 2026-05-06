<div class="crm-content">
    <x-mary-header title="{{ $template->name }}" subtitle="{{ $template->is_system ? 'System template' : 'Custom template' }}">
        <x-slot:actions>
            @can('create crm sms-templates')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.clone')) }}" link="{{ route('laravel-crm.sms-templates.create', ['clone_from' => $template->id]) }}" icon="o-document-duplicate" />
            @endcan
            @if(! $template->is_system)
                @can('edit crm sms-templates')
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.edit')) }}" link="{{ route('laravel-crm.sms-templates.edit', $template) }}" icon="o-pencil-square" class="btn-primary text-white" />
                @endcan
            @endif
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }}" link="{{ route('laravel-crm.sms-templates.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="mb-5">
        <div>
            <div class="text-xs text-base-content/60 mb-2">{{ ucfirst(__('laravel-crm::lang.body')) }}</div>
            <div class="whitespace-pre-wrap font-mono text-sm bg-base-200 p-4 rounded">{{ $template->body }}</div>
        </div>
    </x-mary-card>
</div>
