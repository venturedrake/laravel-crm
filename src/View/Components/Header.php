<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Mary\View\Components\Header as MaryHeader;

class Header extends MaryHeader
{
    public ?string $badges = null;

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div id="{{ $anchor }}" {{ $attributes->class(["mb-10", "mary-header-anchor" => $withAnchor]) }}>
                    <div class="flex flex-wrap gap-5 justify-between items-center">
                        <div>
                            <div @class(["flex", "items-center", "$size font-extrabold", is_string($title) ? '' : $title?->attributes->get('class') ]) >
                                @if($withAnchor)
                                    <a href="#{{ $anchor }}">
                                @endif

                                @if($icon)
                                    <x-mary-icon name="{{ $icon }}" class="{{ $iconClasses }}" />
                                @endif

                                <span @class(["ml-2" => $icon])>{{ $title }}</span>

                                @if($withAnchor)
                                    </a>
                                @endif
                            </div>

                            @if($subtitle)
                                <div @class(["text-base-content/50 text-sm mt-1", is_string($subtitle) ? '' : $subtitle?->attributes->get('class') ]) >
                                    {{ $subtitle }}
                                </div>
                            @endif
                            
                            @if($badges)
                                <div class="mt-1">
                                    {{ $badges }}
                                </div>
                            @endif
                        </div>

                        @if($middle)
                            <div @class(["flex items-center justify-center gap-3 grow order-last sm:order-none", is_string($middle) ? '' : $middle?->attributes->get('class')])>
                                <div class="w-full lg:w-auto">
                                    {{ $middle }}
                                </div>
                            </div>
                        @endif

                        <div @class(["flex items-center gap-1", is_string($actions) ? '' : $actions?->attributes->get('class') ]) >
                            {{ $actions}}
                        </div>
                    </div>

                    @if($separator)
                        <hr class="border-t-[length:var(--border)] border-base-content/10 mt-3" />

                        @if($progressIndicator)
                            <div class="h-0.5 -mt-4 mb-4">
                                <progress
                                    class="progress {{ $progressIndicatorClass }} w-full h-[var(--border)]"
                                    wire:loading

                                    @if($progressTarget())
                                        wire:target="{{ $progressTarget() }}"
                                     @endif></progress>
                            </div>
                        @endif
                    @endif
                </div>
                HTML;
    }
}
