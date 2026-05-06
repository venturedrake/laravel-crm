<x-mary-drawer
    wire:model="showPreview"
    title="{{ ucfirst(__('laravel-crm::lang.preview')) }}"
    separator
    with-close-button
    close-on-escape
    class="w-full lg:w-1/2"
    right
>
    <div class="mx-auto max-w-sm">
        <div class="mockup-phone">
            <div class="mockup-phone-camera"></div>
            <div class="mockup-phone-display">
                <div class="p-4 pt-12 bg-base-100 h-full">
                    @if($previewText)
                        <div class="chat chat-start">
                            <div class="chat-bubble chat-bubble-primary whitespace-pre-wrap text-sm">{{ $previewText }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-mary-drawer>
