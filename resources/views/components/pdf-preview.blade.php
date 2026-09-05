{{--
    Hand-rolled slide-over rather than <x-mary-drawer>: the Mary drawer's open
    state lives in its own x-data (or a Livewire property) and can't be driven
    by a window event, and its x-mary-card wrapper forces px-8 padding that
    fights a full-bleed canvas area.
--}}
<div
    x-data="crmPdfPreview"
    @crm-pdf-preview.window="open($event.detail)"
    @keydown.escape.window="close()"
    x-cloak
>
    {{-- BACKDROP --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0 z-40 bg-black/50"
    ></div>

    {{-- PANEL --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-base-100 shadow-2xl sm:w-11/12 lg:w-3/5 xl:w-1/2"
        role="dialog"
        aria-modal="true"
    >
        {{-- TOOLBAR --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-base-300 px-4 py-3">
            <div class="min-w-0 flex-1">
                <div class="truncate font-semibold" x-text="title"></div>
                <div class="text-xs opacity-60" x-show="pages > 0">
                    <span x-text="pages"></span>
                    <span x-text="pages === 1 ? @js(__('laravel-crm::lang.page')) : @js(__('laravel-crm::lang.pages'))"></span>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <x-mary-button
                    icon="o-magnifying-glass-minus"
                    class="btn-sm btn-square btn-ghost"
                    tooltip="{{ ucfirst(__('laravel-crm::lang.zoom_out')) }}"
                    x-bind:disabled="! doc || scale <= 0.5"
                    @click="zoomOut()"
                />
                <span class="w-12 text-center text-xs tabular-nums opacity-70" x-text="zoomPercent + '%'"></span>
                <x-mary-button
                    icon="o-magnifying-glass-plus"
                    class="btn-sm btn-square btn-ghost"
                    tooltip="{{ ucfirst(__('laravel-crm::lang.zoom_in')) }}"
                    x-bind:disabled="! doc || scale >= 3"
                    @click="zoomIn()"
                />

                {{-- A plain <a>, not <x-mary-button link>: Mary's button adds
                     wire:navigate, which would try to SPA-navigate to a PDF. --}}
                <a
                    x-bind:href="downloadUrl"
                    class="btn btn-sm btn-outline ml-1"
                >
                    <x-mary-icon name="o-arrow-down-tray" class="h-4 w-4" />
                    <span class="hidden sm:inline">{{ ucfirst(__('laravel-crm::lang.download')) }}</span>
                </a>

                <x-mary-button
                    icon="o-x-mark"
                    class="btn-sm btn-square btn-ghost ml-1"
                    tooltip="{{ ucfirst(__('laravel-crm::lang.close')) }}"
                    @click="close()"
                />
            </div>
        </div>

        {{-- BODY --}}
        <div class="relative flex-1 overflow-auto bg-base-300 p-4">
            {{-- pdf.js writes its <canvas> elements straight in here. Kept in
                 the flow while zooming — the spinner overlays it rather than
                 replacing it, so a re-render doesn't blank the panel. --}}
            <div x-ref="pages" x-show="! error"></div>

            <div
                x-show="loading"
                class="pointer-events-none absolute inset-0 flex items-center justify-center bg-base-300/60"
            >
                <x-mary-loading class="loading-lg text-primary" />
            </div>

            <div x-show="error" class="flex h-full items-center justify-center">
                <x-mary-alert
                    icon="o-exclamation-triangle"
                    class="alert-error max-w-md text-white"
                >
                    <span x-text="error"></span>
                </x-mary-alert>
            </div>
        </div>
    </div>
</div>
