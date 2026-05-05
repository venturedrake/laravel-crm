<div class="crm-content">
    <x-mary-header title="{{ $template->name }}" subtitle="{{ $template->is_system ? 'System template' : 'Custom template' }}">
        <x-slot:actions>
            @can('create crm email-templates')
                <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.clone')) }}" link="{{ route('laravel-crm.email-templates.create', ['clone_from' => $template->id]) }}" icon="o-document-duplicate" />
            @endcan
            @if(! $template->is_system)
                @can('edit crm email-templates')
                    <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.edit')) }}" link="{{ route('laravel-crm.email-templates.edit', $template) }}" icon="o-pencil-square" class="btn-primary text-white" />
                @endcan
            @endif
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }}" link="{{ route('laravel-crm.email-templates.index') }}" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="mb-5">
        <div>
            <div class="text-xs text-base-content/60">{{ ucfirst(__('laravel-crm::lang.subject')) }}</div>
            <div class="mb-3">{{ $template->subject }}</div>
            <div class="text-xs text-base-content/60 mb-2">{{ ucfirst(__('laravel-crm::lang.preview')) }}</div>
            <iframe class="w-full h-[600px] border border-base-300 rounded" srcdoc="{{ $template->body }}"></iframe>
        </div>
    </x-mary-card>
</div>
