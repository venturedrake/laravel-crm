<x-mary-drawer
    wire:model="showPreview"
    title="{{ ucfirst(__('laravel-crm::lang.preview')) }}"
    separator
    with-close-button
    close-on-escape
    class="w-full lg:w-1/2"
    right
>
    <div x-data="{ device: 'desktop' }">
        <div class="flex justify-center mb-4">
            <div class="join">
                <button type="button" class="join-item btn btn-sm" :class="device === 'mobile' ? 'btn-primary text-white' : 'btn-ghost'" @click="device = 'mobile'">
                    {{ ucfirst(__('laravel-crm::lang.mobile')) }}
                </button>
                <button type="button" class="join-item btn btn-sm" :class="device === 'tablet' ? 'btn-primary text-white' : 'btn-ghost'" @click="device = 'tablet'">
                    {{ ucfirst(__('laravel-crm::lang.tablet')) }}
                </button>
                <button type="button" class="join-item btn btn-sm" :class="device === 'desktop' ? 'btn-primary text-white' : 'btn-ghost'" @click="device = 'desktop'">
                    {{ ucfirst(__('laravel-crm::lang.desktop')) }}
                </button>
            </div>
        </div>
        @if($previewHtml)
            <div class="mx-auto transition-all duration-300 overflow-hidden border border-base-300 rounded-lg"
                 :style="{ maxWidth: device === 'mobile' ? '375px' : device === 'tablet' ? '768px' : '100%' }">
                <iframe srcdoc="{{ $previewHtml }}" width="100%" style="height:calc(100vh - 140px);border:none;display:block;" sandbox="allow-same-origin"></iframe>
            </div>
        @endif
    </div>
</x-mary-drawer>
