<?php

namespace VentureDrake\LaravelCrm\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimelineItem extends Component
{
    public string $uuid;

    public function __construct(
        public string $title,
        public ?string $id = null,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?string $icon = null,
        public ?bool $pending = false,
        public ?bool $first = false,
        public ?bool $last = false,

        public ?string $connectorPendingClass = 'border-s-base-300',
        public ?string $connectorActiveClass = '!border-s-primary',
        public ?string $bulletActiveClass = '!bg-primary',
        public ?string $bulletPendingClass = 'bg-base-300',

        public $activity = null,
        public $activityType = null
    ) {
        $this->uuid = 'crm-timeline-item'.md5(serialize($this)).$id;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div>
                    <!-- Last item `border cut` -->
                    <div @class(["border-s-2 $connectorPendingClass h-5 -mb-5" => $last, $connectorActiveClass => !$pending])>
                    </div>

                    <!-- WRAPPER THAT ALSO ACTS A LINE CONNECTOR -->
                    <div @class([
                            "border-s-2 $connectorPendingClass ps-8 py-3",
                            $connectorActiveClass => !$pending,
                            "pt-0" => $first,
                            "!border-s-0" => $last,
                         ])
                    >
                        <!-- BULLET -->
                        <div @class([
                                "w-4 h-4 -mb-5 -ms-[41px] $bulletPendingClass rounded-full",
                                $bulletActiveClass => !$pending,
                                "!-ms-[39px]" => $last,
                                "w-8 h-8 !-ms-[48px] -mb-7" => $icon
                             ])
                        >
                            <!-- ICON -->
                            @if($icon)
                                <x-mary-icon :name="$icon" @class(["ms-2 mt-1 w-4 h-4", "text-base-100" => !$pending ])  />
                            @endif
                        </div>

                        <!-- TITLE -->
                        <div @class(["font-bold mb-1"])>{{ $title }}</div>

                        <!-- SUBTITLE -->
                        @if($subtitle)
                            <div class="text-xs text-base-content/30 font-bold">{{ $subtitle }}</div>
                        @endif

                        <!-- DESCRIPTION -->
                        @if($description)
                            <div class="text-sm mt-3">
                                {{ $description }}
                            </div>
                        @endif
                        
                        @if($activity && $activityType)
                            <div class="ms-10 mt-2">
                                @livewire('crm-' . $activityType . '-item', [$activityType => $activity->recordable, 'related' => false], key('activity-' . $activityType . '-' . $activity->id))
                            </div>
                        @endif                   
                    </div>
                </div>
            HTML;
    }
}
