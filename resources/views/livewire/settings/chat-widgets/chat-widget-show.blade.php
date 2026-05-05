<div class="crm-content">
    <x-mary-header title="{{ $widget->name }}" subtitle="{{ ucfirst(__('laravel-crm::lang.chat_widget')) }}" progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.back')) }}" link="{{ url(route('laravel-crm.chat-widgets.index')) }}" icon="fas.angle-double-left" />
            <x-mary-button label="{{ ucfirst(__('laravel-crm::lang.edit')) }}" link="{{ url(route('laravel-crm.chat-widgets.edit', $widget)) }}" icon="o-pencil-square" class="btn-primary text-white" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.embed_snippet')) }}" subtitle="{{ __('laravel-crm::lang.embed_snippet_subtitle') }}" shadow>
        <pre class="bg-base-200 p-4 rounded-lg overflow-x-auto text-xs"><code>{{ $snippet }}</code></pre>
    </x-mary-card>

    <x-mary-card title="{{ ucfirst(__('laravel-crm::lang.preview')) }}" shadow class="mt-4">
        <iframe src="{{ route('laravel-crm.portal.chat.widget', ['publicKey' => $widget->public_key]) }}"
                class="w-[380px] h-[560px] border rounded-lg"></iframe>
    </x-mary-card>
</div>

